<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeBox;
use App\Models\News;
use App\Models\Publication;
use App\Models\Setting;
use App\Models\User;
use App\Support\Theme;

function themeUser(string $role, string $username): User
{
    return User::forceCreate([
        'name' => $username, 'username' => $username, 'email' => "$username@example.com",
        'password' => 'secret-123', 'role' => $role, 'is_active' => true,
    ]);
}

beforeEach(function () {
    Category::forceCreate(['id' => 1, 'name' => 'النجاة في الصحف', 'is_active' => true]);
    Category::forceCreate(['id' => 4, 'name' => 'النجاة في الإذاعة', 'is_active' => true]);
    Category::forceCreate(['id' => 5, 'name' => 'النجاة في التلفزيون', 'is_active' => true]);

    $day = now()->toDateString();
    $make = function (array $attrs, int $category) use ($day) {
        $news = News::forceCreate($attrs + ['type' => 1, 'is_active' => true, 'published_date' => $day]);
        $news->categories()->attach($category);

        return $news;
    };

    $make(['title' => 'خبر بوصف كامل', 'description' => 'وصف الخبر الرئيسي في الصحف اليوم', 'body' => '<p>نص الخبر</p>', 'image' => 'upload/a.jpg'], 1);
    $make(['title' => 'خبر', 'image' => 'upload/clip.jpg'], 1); // قصاصة بلا نص
    $make(['title' => 'لقاء إذاعي', 'type' => 2, 'sound_url' => 'https://example.com/a.mp3'], 4);
    $make(['title' => 'تقرير تلفزيوني', 'type' => 3, 'video_url' => 'https://www.youtube.com/watch?v=abc123XYZ_-'], 5);

    HomeBox::forceCreate(['context' => 'home', 'position' => 1, 'category_id' => 1, 'items_limit' => 5, 'type' => 1]);
    HomeBox::forceCreate(['context' => 'home', 'position' => 2, 'category_id' => 5, 'items_limit' => 3, 'type' => 4]);
    HomeBox::forceCreate(['context' => 'home', 'position' => 3, 'category_id' => 4, 'items_limit' => 3, 'type' => 3]);

    $this->publication = Publication::forceCreate(['title' => 'نشرة اليوم', 'publication_date' => $day, 'is_active' => true, 'pdf_version' => 5]);
    $this->news = News::where('title', 'خبر بوصف كامل')->first();
});

it('keeps the classic front end by default', function () {
    expect(Theme::active())->toBe('classic');

    $this->get('/')->assertOk()
        ->assertSee('css/style.css', false)
        ->assertDontSee('themes/modern/site.css', false);
});

it('renders every public page with the new theme once it is chosen', function () {
    Setting::put(['site_theme' => 'modern']);

    $this->get('/')->assertOk()
        ->assertSee('themes/modern/site.css', false)
        ->assertDontSee('css/style.css', false)
        ->assertSee('خبر بوصف كامل')
        ->assertSee('media-pair', false)            // المرئيات والصوتيات المتتاليان في شريط واحد
        ->assertSee('data-youtube="abc123XYZ_-"', false)
        ->assertSee('data-audio', false)
        ->assertSee('غلاف نشرة', false);             // الغلاف مرسوم من قالب النشرة

    $this->get('show/'.$this->news->id)->assertOk()->assertSee('خبر بوصف كامل')->assertSee('من نفس النشرة')->assertSee('نص الخبر', false);
    $this->get('category/1')->assertOk()->assertSee('النجاة في الصحف');
    $this->get('search/'.rawurlencode('وصف'))->assertOk()->assertSee('نتائج البحث');
    $this->get('search')->assertOk();
    $this->get('archive.html')->assertOk()->assertSee('أرشيف النشرات')->assertSee('issue-cover', false);
    $this->get('publication/'.$this->publication->id)->assertOk()->assertSee('خبر بوصف كامل');
    $this->get('/?date=2000-01-01')->assertOk()->assertSee('لم تُنشر أخبار');
});

it('shows the closed page with the active theme', function () {
    Setting::put(['site_theme' => 'modern', 'close_site' => '1', 'close_site_cause' => 'صيانة مجدولة']);

    $this->get('/')->assertStatus(503)->assertSee('صيانة مجدولة')->assertSee('themes/modern/site.css', false);
});

it('lets settings managers preview the other theme without affecting visitors', function () {
    $admin = themeUser('admin', 'boss');

    // الزائر: ?theme لا أثر له
    $this->get('/?theme=modern')->assertOk()->assertDontSee('themes/modern/site.css', false);

    $this->actingAs($admin);
    $this->get('/?theme=modern')->assertOk()->assertSee('themes/modern/site.css', false)->assertSee('إنهاء المعاينة');
    $this->get('archive.html')->assertOk()->assertSee('themes/modern/site.css', false); // المعاينة تبقى في الجلسة
    $this->get('/?theme=off')->assertOk()->assertDontSee('themes/modern/site.css', false);

    // المحرر لا يملك settings.general
    $this->actingAs(themeUser('editor', 'writer'));
    $this->get('/?theme=modern')->assertOk()->assertDontSee('themes/modern/site.css', false);
});

it('saves the theme from the general settings tab and can switch back', function () {
    $this->actingAs(themeUser('admin', 'boss'));

    $this->get(route('admin.settings.edit'))->assertOk()->assertSee('واجهة الموقع')->assertSee('الواجهة الجديدة');

    $this->put(route('admin.settings.update'), ['tab' => 'general', 'setting' => ['site_theme' => 'modern']])->assertRedirect();
    expect(Theme::active())->toBe('modern');

    $this->put(route('admin.settings.update'), ['tab' => 'general', 'setting' => ['site_theme' => 'nope']])->assertSessionHasErrors('setting.site_theme');
    expect(Theme::active())->toBe('modern');

    $this->put(route('admin.settings.update'), ['tab' => 'general', 'setting' => ['site_theme' => 'classic']])->assertRedirect();
    expect(Theme::active())->toBe('classic');
    $this->get('/')->assertSee('css/style.css', false);
});

it('previews a home box with the active theme', function () {
    $this->actingAs(themeUser('admin', 'boss'));
    $query = ['context' => 'home', 'category_id' => 1, 'type' => 1];

    $this->get(route('admin.settings.preview', $query))->assertOk()->assertSee('css/style.css', false);

    Setting::put(['site_theme' => 'modern']);
    $this->get(route('admin.settings.preview', $query))->assertOk()->assertSee('themes/modern/site.css', false)->assertSee('خبر بوصف كامل');
});

it('shows banners and code boxes in the new theme', function () {
    Setting::put(['site_theme' => 'modern']);
    $banner = Banner::forceCreate(['title' => 'بانر التبرع', 'image' => 'upload/banner.jpg', 'url' => 'https://example.com', 'is_active' => true]);
    HomeBox::forceCreate(['context' => 'home', 'position' => 4, 'banner_id' => $banner->id, 'items_limit' => 0, 'type' => 0]);
    HomeBox::forceCreate(['context' => 'home', 'position' => 5, 'code' => '<div id="custom-code">كود</div>', 'items_limit' => 0, 'type' => 0]);

    $this->get('/')->assertOk()->assertSee('بانر التبرع')->assertSee('<div id="custom-code">', false);
});
