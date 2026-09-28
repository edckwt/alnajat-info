<?php

use App\Http\Controllers\Site\CategoryController;
use App\Models\Category;
use App\Models\News;
use App\Models\Newspaper;
use App\Models\Setting;
use App\Support\Media;

beforeEach(function () {
    Setting::put(['site_theme' => 'modern']);
    Category::forceCreate(['id' => 1, 'name' => 'النجاة في الصحف', 'is_active' => true]);
    $this->paper = Newspaper::forceCreate(['name' => 'جريدة القبس', 'logo' => 'upload/newspaper_1.png', 'is_active' => true]);

    $this->news = News::forceCreate([
        'title' => 'خبر من القبس', 'description' => 'وصف الخبر', 'type' => 1, 'is_active' => true,
        'published_date' => now()->toDateString(), 'newspaper_id' => $this->paper->id, 'newspaper_number' => '17512',
        'image' => 'upload/news_1.jpg',
    ]);
    $this->news->categories()->attach(1);
});

it('shows the newspaper logo in the category list and on the news page', function () {
    $logo = Media::url('upload/newspaper_1.png');

    $this->get('category/1')->assertOk()
        ->assertSee('class="paper paper-sm has-logo"', false)
        ->assertSee('src="'.$logo.'"', false)
        ->assertSee('alt="جريدة القبس"', false);

    $this->get('show/'.$this->news->id)->assertOk()
        ->assertSee('article-source', false)
        ->assertSee('class="paper paper-lg has-logo"', false)
        ->assertSee('src="'.$logo.'"', false)
        ->assertSee('العدد 17512');
});

it('shows the newspaper name instead of the logo when the setting asks for it', function () {
    Setting::put(['newspaper_name' => '1']);

    $this->get('show/'.$this->news->id)->assertOk()
        ->assertSee('جريدة القبس')
        ->assertDontSee('src="'.Media::url('upload/newspaper_1.png').'"', false);
});

it('lets the visitor switch the category between grid (default) and list, and remembers it', function () {
    $this->get('category/1')->assertOk()
        ->assertSee('view-switch', false)
        ->assertSee('nc nc-card', false)
        ->assertDontSee('nc nc-row', false);
    $this->get('category/1?view=weird')->assertOk()->assertSee('nc nc-card', false); // قيمة غير معروفة = الشبكي

    $this->get('category/1?view=list')->assertOk()
        ->assertSee('news-list', false)
        ->assertSee('nc nc-row', false)
        ->assertCookie(CategoryController::LAYOUT_COOKIE, 'list');

    // الاختيار محفوظ في الكوكي للزيارة التالية
    $this->withCookie(CategoryController::LAYOUT_COOKIE, 'list')->get('category/1')->assertOk()->assertSee('nc nc-row', false);

    // ?view يغلب الكوكي المحفوظ (withCookie يبقى مع كل الطلبات التالية في الاختبار)
    $this->get('category/1?view=grid')->assertOk()->assertSee('nc nc-card', false)->assertCookie(CategoryController::LAYOUT_COOKIE, 'grid');
});
