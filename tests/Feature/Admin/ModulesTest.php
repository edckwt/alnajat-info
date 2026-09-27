<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeBox;
use App\Models\News;
use App\Models\Newspaper;
use App\Models\Publication;
use App\Models\Setting;
use App\Models\Upload;
use App\Models\User;
use App\Services\ImageUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->uploadDir = sys_get_temp_dir().'/alnajat-test-upload-'.uniqid();
    $this->app->instance(ImageUploader::class, new ImageUploader($this->uploadDir));

    $this->admin = User::forceCreate(['name' => 'المدير', 'username' => 'admin', 'email' => 'admin@example.com',
        'password' => 'secret-123', 'role' => 'admin', 'is_active' => true]);
    $this->editor = User::forceCreate(['name' => 'محرر', 'username' => 'editor', 'email' => 'editor@example.com',
        'password' => 'secret-123', 'role' => 'editor', 'is_active' => true]);
});

afterEach(fn () => File::deleteDirectory($this->uploadDir));

it('opens every admin page', function (string $route) {
    Category::forceCreate(['id' => 1, 'name' => 'محليات', 'is_active' => true]);

    $this->actingAs($this->admin)->get(route($route))->assertOk();
})->with([
    'admin.dashboard', 'admin.news.index', 'admin.news.order', 'admin.publications.index', 'admin.publications.create',
    'admin.categories.index', 'admin.categories.create', 'admin.newspapers.index', 'admin.newspapers.create',
    'admin.banners.index', 'admin.banners.create', 'admin.uploads.index', 'admin.users.index', 'admin.users.create',
    'admin.settings.edit', 'admin.roles.index', 'admin.roles.create', 'admin.pdf-templates.index', 'admin.pdf-templates.create',
    'admin.missing-links.index', 'admin.profile.show',
]);

it('keeps editors out of users and settings', function (string $route) {
    $this->actingAs($this->editor)->get(route($route))->assertForbidden();
})->with(['admin.users.index', 'admin.users.create', 'admin.settings.edit']);

it('lets editors manage content', function () {
    $this->actingAs($this->editor)->get(route('admin.categories.index'))->assertOk();
    $this->actingAs($this->editor)->get(route('admin.publications.index'))->assertOk();
});

it('manages categories', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), ['name' => 'إغاثة', 'is_active' => '1'])
        ->assertRedirect(route('admin.categories.index'));

    $category = Category::sole();
    expect($category->created_by)->toBe($this->admin->id);

    $news = News::forceCreate(['title' => 'x', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25']);
    $news->categories()->attach($category);

    $this->put(route('admin.categories.update', $category), ['name' => 'الإغاثة', 'is_active' => '0']);
    expect($category->fresh()->name)->toBe('الإغاثة')->and($category->fresh()->is_active)->toBeFalse();

    $this->delete(route('admin.categories.destroy', $category));
    expect(Category::count())->toBe(0)->and(News::count())->toBe(1)->and($news->categories()->count())->toBe(0);
});

it('manages newspapers with a dropped logo', function () {
    $this->actingAs($this->admin)->post(route('admin.newspapers.store'), [
        'name' => 'القبس', 'type' => '0', 'is_active' => '1',
        'logo_file' => UploadedFile::fake()->image('logo.png', 300, 120),
    ])->assertSessionHasNoErrors();

    $paper = Newspaper::sole();
    expect($paper->logo)->toStartWith('upload/newspaper_')
        ->and(File::exists($this->uploadDir.'/'.basename($paper->logo)))->toBeTrue();

    $news = News::forceCreate(['title' => 'x', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25', 'newspaper_id' => $paper->id]);
    $this->delete(route('admin.newspapers.destroy', $paper));
    expect($news->fresh()->newspaper_id)->toBeNull();
});

it('manages banners', function () {
    $this->actingAs($this->admin)->post(route('admin.banners.store'), [
        'title' => 'تبرع الآن', 'url' => 'https://example.org', 'is_active' => '1',
        'image_file' => UploadedFile::fake()->image('b.jpg', 800, 200),
    ])->assertSessionHasNoErrors();

    $banner = Banner::sole();
    $this->patch(route('admin.banners.toggle', $banner));
    expect($banner->fresh()->is_active)->toBeFalse();
});

it('creates and edits a publication without touching its news', function () {
    News::forceCreate(['title' => 'x', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25']);

    $this->actingAs($this->admin)->post(route('admin.publications.store'), [
        'publication_date' => '2026-09-25', 'pdf_version' => 5, 'is_active' => '1', 'cover' => '0',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $publication = Publication::sole();
    expect($publication->title)->toBe('2026-09-25');

    $this->get(route('admin.publications.edit', $publication))->assertOk()->assertSee('1 خبراً');

    $this->post(route('admin.publications.store'), ['publication_date' => '2026-09-25', 'pdf_version' => 5])
        ->assertSessionHasErrors('publication_date');

    $this->delete(route('admin.publications.destroy', $publication));
    expect(Publication::count())->toBe(0)->and(News::count())->toBe(1);
});

it('uploads several files at once', function () {
    $this->actingAs($this->editor)->post(route('admin.uploads.store'), [
        'files' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->create('b.pdf', 100, 'application/pdf')],
    ])->assertSessionHasNoErrors();

    expect(Upload::count())->toBe(2)
        ->and(Upload::pluck('extension')->sort()->values()->all())->toBe(['jpg', 'pdf'])
        ->and(Upload::first()->user_id)->toBe($this->editor->id);
});

it('manages users and protects the admin from locking themselves out', function () {
    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'جديد', 'username' => 'newbie', 'email' => 'new@example.com',
        'password' => 'long-enough', 'password_confirmation' => 'long-enough', 'role' => 'editor', 'is_active' => '1',
    ])->assertSessionHasNoErrors();

    $new = User::where('username', 'newbie')->sole();
    expect(Hash::check('long-enough', $new->password))->toBeTrue();

    // المدير يعدّل نفسه: الصلاحية والتفعيل لا يتغيران
    $this->put(route('admin.users.update', $this->admin), [
        'name' => 'المدير', 'username' => 'admin', 'role' => 'editor', 'is_active' => '0',
    ])->assertSessionHasNoErrors();
    expect($this->admin->fresh()->role)->toBe('admin')->and($this->admin->fresh()->is_active)->toBeTrue();

    $this->delete(route('admin.users.destroy', $this->admin))->assertSessionHasErrors('user');
    $this->delete(route('admin.users.destroy', $new));
    expect(User::where('username', 'newbie')->exists())->toBeFalse();
});

it('clears the legacy hash when an admin sets a new password', function () {
    $legacy = User::forceCreate(['name' => 'قديم', 'username' => 'old', 'legacy_password' => md5('x'), 'role' => 'editor', 'is_active' => true]);

    $this->actingAs($this->admin)->put(route('admin.users.update', $legacy), [
        'name' => 'قديم', 'username' => 'old', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        'role' => 'editor', 'is_active' => '1',
    ])->assertSessionHasNoErrors();

    expect($legacy->fresh()->legacy_password)->toBeNull();
});

it('saves settings and home boxes', function () {
    Category::forceCreate(['id' => 1, 'name' => 'محليات', 'is_active' => true]);

    $this->actingAs($this->admin)->put(route('admin.settings.update'), [
        'tab' => 'home',
        'setting' => ['site_title' => 'النجاة', 'close_site' => '0', 'not_allowed_key' => 'x'],
        'boxes' => ['home' => [1 => ['category_id' => 1, 'items_limit' => 4, 'type' => 2, 'banner_id' => '', 'code' => '']]],
    ])->assertRedirect(route('admin.settings.edit', ['tab' => 'home']));

    expect(Setting::get('site_title'))->toBe('النجاة')
        ->and(Setting::where('key', 'not_allowed_key')->exists())->toBeFalse();

    $box = HomeBox::where(['context' => 'home', 'position' => 1])->sole();
    expect($box->category_id)->toBe(1)->and($box->items_limit)->toBe(4)->and($box->type)->toBe(2)->and($box->banner_id)->toBeNull();
});
