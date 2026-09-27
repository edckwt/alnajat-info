<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeBox;
use App\Models\News;
use App\Models\User;
use App\Services\PdfBuilder;

beforeEach(function () {
    $this->admin = User::forceCreate(['name' => 'المدير', 'username' => 'admin', 'email' => 'admin@example.com',
        'password' => 'secret-123', 'role' => 'admin', 'is_active' => true]);
    Category::forceCreate(['id' => 1, 'name' => 'محليات', 'is_active' => true]);
    Category::forceCreate(['id' => 6, 'name' => 'دولي', 'is_active' => true]);
    $this->banner = Banner::forceCreate(['title' => 'تبرع الآن', 'is_active' => true]);
    $this->actingAs($this->admin);
});

it('shows every box with its template picker and the template gallery', function () {
    HomeBox::forceCreate(['context' => 'home', 'position' => 1, 'category_id' => 1, 'items_limit' => 5, 'type' => 6]);

    $this->get(route('admin.settings.edit', ['tab' => 'home']))
        ->assertOk()
        ->assertSee('صناديق الصفحة الرئيسية')
        ->assertSee('معرض القوالب')
        ->assertSee('عمودان')                       // اسم قالب الصندوق 1
        ->assertSee('data-tpl-option="3"', false)   // قوالب الموقع في المعرض
        ->assertSee('data-tpl-option="9"', false);  // و«مشروع تبرع» في معرض النشرة
});

it('keeps only the fields of the chosen box kind', function () {
    HomeBox::forceCreate(['context' => 'home', 'position' => 1, 'category_id' => 1, 'items_limit' => 5, 'type' => 1, 'banner_id' => $this->banner->id, 'code' => '<b>x</b>']);

    $this->put(route('admin.settings.update'), [
        'tab' => 'home',
        'boxes' => ['home' => [
            1 => ['kind' => 'news', 'position' => 1, 'category_id' => 6, 'items_limit' => 3, 'type' => 7, 'banner_id' => $this->banner->id, 'code' => '<b>x</b>'],
            2 => ['kind' => 'banner', 'position' => 2, 'category_id' => 1, 'items_limit' => 4, 'type' => 1, 'banner_id' => $this->banner->id],
            3 => ['kind' => 'empty', 'position' => 3, 'category_id' => 1, 'items_limit' => 4, 'type' => 1],
        ]],
    ])->assertSessionHasNoErrors();

    $boxes = HomeBox::where('context', 'home')->get()->keyBy('position');

    expect($boxes[1]->only(['category_id', 'items_limit', 'type', 'banner_id', 'code']))
        ->toBe(['category_id' => 6, 'items_limit' => 3, 'type' => 7, 'banner_id' => null, 'code' => null])
        ->and($boxes[2]->category_id)->toBeNull()->and($boxes[2]->banner_id)->toBe($this->banner->id)
        ->and($boxes[3]->category_id)->toBeNull()->and($boxes[3]->items_limit)->toBe(0);
});

it('saves the new order after dragging', function () {
    // الصندوق الذي كان ثانياً سُحب إلى الأعلى
    $this->put(route('admin.settings.update'), [
        'tab' => 'pdf_boxes',
        'boxes' => ['pdf' => [
            1 => ['kind' => 'news', 'position' => 2, 'category_id' => 1, 'items_limit' => 5, 'type' => 1],
            2 => ['kind' => 'news', 'position' => 1, 'category_id' => 6, 'items_limit' => 2, 'type' => 9],
        ]],
    ])->assertSessionHasNoErrors();

    expect(HomeBox::where(['context' => 'pdf', 'position' => 1])->sole()->category_id)->toBe(6)
        ->and(HomeBox::where(['context' => 'pdf', 'position' => 2])->sole()->category_id)->toBe(1);
});

it('asks for the missing field of each kind', function () {
    $this->put(route('admin.settings.update'), [
        'boxes' => ['home' => [
            1 => ['kind' => 'news', 'position' => 1, 'category_id' => '', 'type' => 1],
            2 => ['kind' => 'banner', 'position' => 2, 'banner_id' => ''],
            3 => ['kind' => 'code', 'position' => 3, 'code' => ''],
        ]],
    ])->assertSessionHasErrors(['boxes.home.1.category_id', 'boxes.home.2.banner_id', 'boxes.home.3.code']);
});

it('previews a home box with the latest news of its category', function () {
    foreach (range(1, 3) as $i) {
        News::forceCreate(['title' => "خبر {$i}", 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-2'.$i])->categories()->attach(1);
    }

    $this->get(route('admin.settings.preview', ['context' => 'home', 'category_id' => 1, 'type' => 6, 'limit' => 2]))
        ->assertOk()
        ->assertSee('charity-news')           // قالب «عمودان»
        ->assertSee('خبر 3')->assertSee('خبر 2')->assertDontSee('خبر 1');

    $this->get(route('admin.settings.preview', ['context' => 'home', 'category_id' => 6, 'type' => 1]))
        ->assertOk()->assertSee('لا توجد أخبار منشورة');
});

it('previews a pdf box as a pdf file', function () {
    $this->app->instance(PdfBuilder::class, new class extends PdfBuilder
    {
        public function preview(Category $category, int $type, int $limit): string
        {
            return "%PDF-1.4 {$category->id}-{$type}-{$limit}";
        }
    });

    $response = $this->get(route('admin.settings.preview', ['context' => 'pdf', 'category_id' => 1, 'type' => 9, 'limit' => 50]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($response->getContent())->toBe('%PDF-1.4 1-9-10'); // المعاينة 10 أخبار كحد أقصى
});

it('rejects bad preview requests and keeps editors out', function () {
    $this->get(route('admin.settings.preview', ['context' => 'x', 'category_id' => 1, 'type' => 1]))->assertNotFound();
    $this->get(route('admin.settings.preview', ['context' => 'home', 'category_id' => 999, 'type' => 1]))->assertNotFound();
    $this->get(route('admin.settings.font', 'no-such-font'))->assertNotFound();

    $editor = User::forceCreate(['name' => 'محرر', 'username' => 'editor', 'email' => 'e@example.com',
        'password' => 'secret-123', 'role' => 'editor', 'is_active' => true]);
    $this->actingAs($editor)
        ->get(route('admin.settings.preview', ['context' => 'home', 'category_id' => 1, 'type' => 1]))
        ->assertForbidden();
});
