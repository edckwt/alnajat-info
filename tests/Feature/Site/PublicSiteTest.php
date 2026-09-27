<?php

use App\Models\Category;
use App\Models\HomeBox;
use App\Models\News;
use App\Models\Publication;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->category = Category::forceCreate(['id' => 1, 'name' => 'محليات', 'is_active' => true]);

    $this->today = News::forceCreate([
        'title' => 'خبر اليوم', 'description' => 'وصف خبر اليوم', 'body' => '<p>نص خبر اليوم</p>',
        'type' => 1, 'is_active' => true, 'published_date' => now()->toDateString(),
    ]);
    $this->old = News::forceCreate([
        'title' => 'خبر قديم', 'body' => '<p>نص قديم</p>',
        'type' => 1, 'is_active' => true, 'published_date' => '2026-01-10',
    ]);
    $this->hidden = News::forceCreate([
        'title' => 'خبر غير منشور', 'type' => 1, 'is_active' => false, 'published_date' => now()->toDateString(),
    ]);

    foreach ([$this->today, $this->old, $this->hidden] as $news) {
        $news->categories()->attach($this->category->id);
    }

    HomeBox::forceCreate(['context' => 'home', 'position' => 1, 'category_id' => 1, 'items_limit' => 5, 'type' => 1]);

    $this->publication = Publication::forceCreate([
        'title' => 'نشرة النجاة', 'publication_date' => '2026-01-10', 'is_active' => true, 'pdf_version' => 5,
    ]);
});

it('shows today news on the home page, all news with ?all and a given day with ?date', function () {
    $this->get('/')->assertOk()->assertSee('خبر اليوم')->assertDontSee('خبر قديم')->assertDontSee('خبر غير منشور');
    $this->get('/?all')->assertOk()->assertSee('خبر اليوم')->assertSee('خبر قديم');
    $this->get('/?date=2026-01-10')->assertOk()->assertSee('خبر قديم')->assertDontSee('خبر اليوم');
});

it('shows a news item and counts one view per session', function () {
    $this->get('show/'.$this->today->id)->assertOk()->assertSee('خبر اليوم')->assertSee('نص خبر اليوم', false);
    $this->get('show/'.$this->today->id)->assertOk();

    expect($this->today->fresh()->views)->toBe(1);

    $this->get('show/'.$this->today->id.'?open=pdf')->assertOk();
    expect($this->today->fresh()->pdf_views)->toBe(1);
});

it('hides unpublished news', function () {
    $this->get('show/'.$this->hidden->id)->assertNotFound();
    $this->get('show/999999')->assertNotFound();
});

it('lists a category', function () {
    $this->get('category/1')->assertOk()->assertSee('محليات')->assertSee('خبر اليوم')->assertSee('خبر قديم')->assertDontSee('خبر غير منشور');

    Category::whereKey(1)->update(['is_active' => false]);
    $this->get('category/1')->assertNotFound();
});

it('searches by path and by ?s', function () {
    $this->get('search/'.rawurlencode('قديم'))->assertOk()->assertSee('خبر قديم')->assertDontSee('خبر اليوم');
    $this->get('search?s='.rawurlencode('اليوم'))->assertOk()->assertSee('خبر اليوم')->assertDontSee('خبر قديم');
    $this->get('search')->assertOk();
});

it('shows the archive and a publication page', function () {
    Publication::forceCreate(['title' => 'نشرة مخفية', 'publication_date' => '2026-01-11', 'is_active' => false]);

    $this->get('archive.html')->assertOk()->assertSee('نشرة النجاة')->assertDontSee('نشرة مخفية');
    $this->get('publication/'.$this->publication->id)->assertOk()->assertSee('خبر قديم');
});

it('redirects the old index.php?action= links permanently', function (string $old, string $new) {
    $this->get($old)->assertStatus(301)->assertRedirect(url($new));
})->with([
    ['/?action=show&id=15', 'show/15'],
    ['/?action=show&id=15&read=pdf', 'pdf-show/15'],
    ['/?action=category&id=3', 'category/3'],
    ['/?action=publication&publication_id=7', 'publication/7'],
    ['/?action=publication&publication_id=7&read=pdf', 'pdf-publication/7'],
    ['/?action=publications', 'archive.html'],
    ['/?read=pdf&online=1&publication_id=7', 'pdf-publication/7'],
    ['/?read=pdf&online=1', 'today-news.html'],
]);

it('shows the closed page to visitors but not to logged-in staff', function () {
    Setting::put(['close_site' => '1', 'close_site_cause' => 'صيانة مجدولة']);

    $this->get('/')->assertStatus(503)->assertSee('صيانة مجدولة');

    $user = User::forceCreate([
        'name' => 'مدير', 'username' => 'admin', 'email' => 'admin@example.com',
        'password' => 'secret-123', 'role' => 'admin', 'is_active' => true,
    ]);
    $this->actingAs($user)->get('/')->assertOk();
});

it('builds a full home page (15 boxes) in a handful of queries', function () {
    foreach (range(2, 15) as $i) {
        Category::forceCreate(['id' => $i, 'name' => "قسم {$i}", 'is_active' => true]);
        HomeBox::forceCreate(['context' => 'home', 'position' => $i, 'category_id' => $i, 'items_limit' => 3, 'type' => 1]);

        foreach (range(1, 4) as $n) {
            News::forceCreate(['title' => "خبر {$i}-{$n}", 'type' => 1, 'is_active' => true, 'published_date' => now()->toDateString()])
                ->categories()->attach($i);
        }
    }

    DB::enableQueryLog();
    $response = $this->get('/')->assertOk();
    $queries = count(DB::getQueryLog());

    // النظام القديم: أكثر من 100 استعلام لنفس الصفحة.
    expect($queries)->toBeLessThan(15);

    // كل صندوق يلتزم بحده (3)، والأحدث أولاً.
    $response->assertSee('خبر 15-4')->assertSee('خبر 15-2')->assertDontSee('خبر 15-1');
});
