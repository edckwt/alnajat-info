<?php

use App\Models\Category;
use App\Models\News;
use App\Models\Newspaper;
use App\Models\Publication;
use App\Models\User;
use App\Services\ImageUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // الأقسام بنفس أرقام الموقع الحقيقي (الأنواع مربوطة بها في config/alnajat.php).
    foreach ([1 => 'محليات', 3 => 'تغريدات', 4 => 'صوتيات', 5 => 'مرئيات', 6 => 'دولي', 7 => 'منوعات', 9 => 'دعوة', 10 => 'إغاثة', 11 => 'مشاريع', 12 => 'أخبار'] as $id => $name) {
        Category::forceCreate(['id' => $id, 'name' => $name, 'is_active' => true]);
    }

    // الصور تُكتب في مجلد مؤقت، لا في public/upload (الرابط الرمزي لمجلد الموقع القديم).
    $this->uploadDir = sys_get_temp_dir().'/alnajat-test-upload-'.uniqid();
    $this->app->instance(ImageUploader::class, new ImageUploader($this->uploadDir));

    $this->editor = User::forceCreate([
        'name' => 'محرر', 'username' => 'editor', 'email' => 'editor@example.com',
        'password' => 'secret-123', 'role' => 'editor', 'is_active' => true,
    ]);
    $this->actingAs($this->editor);
});

afterEach(function () {
    File::deleteDirectory($this->uploadDir);
});

function newsPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 1,
        'title' => 'افتتاح مركز جديد',
        'description' => 'وصف قصير',
        'body' => '<p>التفاصيل</p>',
        'published_date' => '2026-09-25',
        'is_active' => '1',
        'categories' => [1, 6],
    ], $overrides);
}

it('keeps guests out', function () {
    auth()->logout();

    $this->get(route('admin.news.index'))->assertRedirect(route('admin.login'));
});

it('lists and searches news', function () {
    News::forceCreate(['title' => 'خبر عن الإغاثة', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25']);
    News::forceCreate(['title' => 'خبر آخر', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-24']);

    $this->get(route('admin.news.index'))->assertOk()->assertSee('خبر عن الإغاثة')->assertSee('خبر آخر');

    $this->get(route('admin.news.index', ['q' => 'الإغاثة']))->assertOk()->assertSee('خبر عن الإغاثة')->assertDontSee('خبر آخر');
    $this->get(route('admin.news.index', ['date' => '2026-09-24']))->assertOk()->assertSee('خبر آخر')->assertDontSee('خبر عن الإغاثة');
});

it('shows the form for every news type', function (int $type) {
    $this->get(route('admin.news.create', ['type' => $type]))
        ->assertOk()
        ->assertSee(config("alnajat.news_types.$type.label"));
})->with([1, 2, 3, 4, 5]);

it('adds a news item with categories and creates the day publication', function () {
    Newspaper::forceCreate(['id' => 4, 'name' => 'القبس', 'type' => 0, 'is_active' => true]);

    $this->post(route('admin.news.store'), newsPayload(['newspaper_id' => 4, 'newspaper_number' => 17]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $news = News::sole();
    expect($news->title)->toBe('افتتاح مركز جديد')
        ->and($news->published_date->toDateString())->toBe('2026-09-25')
        ->and($news->newspaper_id)->toBe(4)
        ->and($news->newspaper_number)->toBe(17)
        ->and($news->created_by)->toBe($this->editor->id)
        ->and($news->categories->pluck('id')->sort()->values()->all())->toBe([1, 6]);

    $publication = Publication::where('publication_date', '2026-09-25')->sole();
    expect($publication->is_active)->toBeTrue()->and($publication->pdf_version)->toBe(5);
});

it('does not create a second publication for the same day', function () {
    $this->post(route('admin.news.store'), newsPayload());
    $this->post(route('admin.news.store'), newsPayload(['title' => 'خبر ثان']));

    expect(Publication::count())->toBe(1);
});

it('files single-category types automatically', function () {
    $this->post(route('admin.news.store'), newsPayload([
        'type' => 4, 'tweet_url' => 'https://x.com/alnajat/status/1', 'categories' => [],
    ]))->assertSessionHasNoErrors();

    expect(News::sole()->categories->pluck('id')->all())->toBe([3]);
});

it('refuses a category that does not belong to the type', function () {
    $this->post(route('admin.news.store'), newsPayload(['categories' => [11]]))
        ->assertSessionHasErrors('categories.0');

    expect(News::count())->toBe(0);
});

it('requires a title and a publish date', function () {
    $this->post(route('admin.news.store'), newsPayload(['title' => '', 'published_date' => '']))
        ->assertSessionHasErrors(['title', 'published_date']);
});

it('uploads the image with the same thumbnails as the old site', function () {
    $this->post(route('admin.news.store'), newsPayload([
        'image_file' => UploadedFile::fake()->image('photo.jpg', 1200, 800),
    ]))->assertSessionHasNoErrors();

    $image = News::sole()->image;
    expect($image)->toStartWith('upload/news_')->toEndWith('.jpg');

    $name = pathinfo($image, PATHINFO_FILENAME);
    expect(File::exists("{$this->uploadDir}/{$name}.jpg"))->toBeTrue()
        ->and(File::exists("{$this->uploadDir}/thumbs/{$name}_350x155.jpg"))->toBeTrue()
        ->and(File::exists("{$this->uploadDir}/thumbs/{$name}_150x150.jpg"))->toBeTrue()
        ->and(File::exists("{$this->uploadDir}/{$name}_thumbnail.jpg"))->toBeTrue();
});

it('updates a news item but never changes its type', function () {
    $news = News::forceCreate(['title' => 'قديم', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25']);

    $this->put(route('admin.news.update', $news), newsPayload(['title' => 'جديد', 'type' => 5, 'categories' => [12]]))
        ->assertRedirect(route('admin.news.edit', $news))
        ->assertSessionHasNoErrors();

    $news->refresh();
    expect($news->title)->toBe('جديد')
        ->and($news->type)->toBe(1)
        ->and($news->updated_by)->toBe($this->editor->id)
        ->and($news->categories->pluck('id')->all())->toBe([12]);
});

it('keeps the old image when no new one is uploaded', function () {
    $news = News::forceCreate(['title' => 'x', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25', 'image' => 'upload/news_1.png']);

    $this->put(route('admin.news.update', $news), newsPayload(['image' => 'upload/news_1.png']));

    expect($news->fresh()->image)->toBe('upload/news_1.png');
});

it('toggles and deletes', function () {
    $news = News::forceCreate(['title' => 'x', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25']);
    $news->categories()->attach(1);

    $this->patch(route('admin.news.toggle', $news));
    expect($news->fresh()->is_active)->toBeFalse();

    $this->delete(route('admin.news.destroy', $news))->assertRedirect(route('admin.news.index'));
    expect(News::count())->toBe(0);
});

it('prefills a duplicate with today as the date', function () {
    $news = News::forceCreate(['title' => 'خبر الأمس', 'type' => 1, 'is_active' => true, 'published_date' => '2020-01-01']);

    $this->get(route('admin.news.create', ['duplicate' => $news->id]))
        ->assertOk()
        ->assertSee('خبر الأمس')
        ->assertSee(now()->toDateString());
});

it('saves the order of a day', function () {
    $a = News::forceCreate(['title' => 'أ', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25', 'sort_order' => 1]);
    $b = News::forceCreate(['title' => 'ب', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25', 'sort_order' => 2]);
    $other = News::forceCreate(['title' => 'يوم آخر', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-24', 'sort_order' => 9]);

    $this->get(route('admin.news.order', ['date' => '2026-09-25']))->assertOk()->assertSeeInOrder(['أ', 'ب']);

    $this->put(route('admin.news.order.update'), ['date' => '2026-09-25', 'ids' => [$b->id, $a->id, $other->id]])
        ->assertRedirect(route('admin.news.order', ['date' => '2026-09-25']));

    expect($b->fresh()->sort_order)->toBe(1)
        ->and($a->fresh()->sort_order)->toBe(2)
        ->and($other->fresh()->sort_order)->toBe(9);
});
