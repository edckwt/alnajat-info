<?php

use App\Models\Category;
use App\Models\News;
use App\Models\Publication;
use App\Models\Setting;
use App\Services\PdfBuilder;
use App\Support\PdfDesign;
use App\Services\PdfCache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('pdf');

    // المولّد الحقيقي (mPDF والخطوط) يُستبدل بعدّاد، والاختبار يفحص التخزين والإبطال.
    $this->builder = new class extends PdfBuilder
    {
        public int $calls = 0;

        public function publication(Publication $publication, ?int $version = null, ?int $sample = null, ?PdfDesign $design = null): string
        {
            $this->calls++;

            return '%PDF-1.4 publication '.$publication->id.' #'.$this->calls;
        }

        public function news(News $news): string
        {
            $this->calls++;

            return '%PDF-1.4 news '.$news->id;
        }
    };
    $this->app->instance(PdfBuilder::class, $this->builder);

    $this->publication = Publication::forceCreate([
        'title' => 'نشرة', 'publication_date' => '2026-01-10', 'is_active' => true, 'pdf_version' => 3,
    ]);
    $this->latest = Publication::forceCreate([
        'title' => 'نشرة أحدث', 'publication_date' => '2026-01-11', 'is_active' => true, 'pdf_version' => 5,
    ]);
    $this->news = News::forceCreate(['title' => 'خبر', 'type' => 1, 'is_active' => true, 'published_date' => '2026-01-10']);
});

it('serves a publication inline, or as a download with ?download=1', function () {
    $this->get('pdf-publication/'.$this->publication->id)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename="Alnajat-News-3-10-01-2026.pdf"');

    $this->get('pdf-publication/'.$this->publication->id.'?download=1')
        ->assertOk()
        ->assertDownload('Alnajat-News-3-10-01-2026.pdf');
});

it('serves the latest published publication as today-news.html', function () {
    Publication::forceCreate(['title' => 'مخفية', 'publication_date' => '2026-01-12', 'is_active' => false]);

    $response = $this->get('today-news.html')->assertOk();

    expect($response->baseResponse->getFile()->getContent())->toContain('publication '.$this->latest->id);
});

it('serves a single news pdf and 404s for hidden items', function () {
    $this->get('pdf-show/'.$this->news->id)->assertOk()->assertHeader('Content-Type', 'application/pdf');

    $this->news->update(['is_active' => false]);
    $this->get('pdf-show/'.$this->news->id)->assertNotFound();

    $this->publication->update(['is_active' => false]);
    $this->get('pdf-publication/'.$this->publication->id)->assertNotFound();
});

it('builds a publication once and reuses the file', function () {
    $this->get('pdf-publication/'.$this->publication->id)->assertOk();
    $this->get('pdf-publication/'.$this->publication->id)->assertOk();

    expect($this->builder->calls)->toBe(1);
    Storage::disk('pdf')->assertExists(PdfCache::publicationFile($this->publication->id));
});

it('rebuilds a publication when a news item of its date changes', function () {
    $cache = app(PdfCache::class);
    $cache->publication($this->publication);

    $this->news->update(['title' => 'عنوان جديد']);
    Storage::disk('pdf')->assertMissing(PdfCache::publicationFile($this->publication->id));

    $cache->publication($this->publication);
    expect($this->builder->calls)->toBe(2);
});

it('rebuilds the old date too when a news item moves to another day', function () {
    $cache = app(PdfCache::class);
    $cache->publication($this->publication);
    $cache->publication($this->latest);

    $this->news->update(['published_date' => '2026-01-11']);

    Storage::disk('pdf')->assertMissing(PdfCache::publicationFile($this->publication->id));
    Storage::disk('pdf')->assertMissing(PdfCache::publicationFile($this->latest->id));
});

it('does not rebuild when only the view counter changes', function () {
    $cache = app(PdfCache::class);
    $cache->publication($this->publication);

    $this->news->increment('views');

    Storage::disk('pdf')->assertExists(PdfCache::publicationFile($this->publication->id));
});

it('expires every file when a global setting changes', function () {
    $cache = app(PdfCache::class);
    $cache->publication($this->publication);
    $file = PdfCache::publicationFile($this->publication->id);

    Setting::put(['pdf_font' => 'cairo']);
    expect($cache->isFresh($file))->toBeFalse();

    $cache->publication($this->publication);
    expect($cache->isFresh($file))->toBeTrue()
        ->and($this->builder->calls)->toBe(2);
});

it('refreshes today\'s publication after today_ttl minutes, but keeps past ones', function () {
    config(['alnajat.pdf.today_ttl' => 15]);
    $today = Publication::forceCreate(['title' => 'اليوم', 'publication_date' => now()->toDateString(), 'is_active' => true]);
    $cache = app(PdfCache::class);

    $cache->publication($today);
    $cache->publication($this->publication);

    $this->travel(16)->minutes();

    $cache->publication($today);
    $cache->publication($this->publication);

    expect($this->builder->calls)->toBe(3);
});

it('warms the latest publications from the command line', function () {
    $this->artisan('pdf:warm', ['--limit' => 2])->assertSuccessful();

    Storage::disk('pdf')->assertExists(PdfCache::publicationFile($this->publication->id));
    Storage::disk('pdf')->assertExists(PdfCache::publicationFile($this->latest->id));
});

it('points the pdf stylesheet at local files', function () {
    $css = app(PdfBuilder::class)->localizeUrls(
        ".a{background:url({site_url}index.php)} .b{background:url('{site_url}images/missing-file.png?v=2')}"
    );

    expect($css)->toContain('url("'.public_path('index.php').'")')
        ->and($css)->toContain('.b{background:none}') // الملف غير موجود: لا يُطلب من الموقع عبر HTTP
        ->and($css)->not->toContain('{site_url}')
        ->and($css)->not->toContain('http');
});

it('never makes mPDF download images from the site itself', function () {
    // ملف موجود: مساره على القرص، بأي صيغة رابط
    expect(PdfBuilder::src('index.php'))->toBe(public_path('index.php'))
        ->and(PdfBuilder::src(url('index.php')))->toBe(public_path('index.php'))
        ->and(PdfBuilder::src('http://www.alnajat.info/index.php?v=1'))->toBe(public_path('index.php'))
        ->and(PdfBuilder::src(public_path('index.php')))->toBe(public_path('index.php'));

    // غير موجود على هذا الجهاز: يُتخطى بدل طلبه من الخادم نفسه
    expect(PdfBuilder::src('upload/not-here.jpg'))->toBeNull()
        ->and(PdfBuilder::src(url('upload/not-here.jpg')))->toBeNull()
        ->and(PdfBuilder::src('https://alnajat.info/upload/not-here.jpg'))->toBeNull()
        ->and(PdfBuilder::src(''))->toBeNull();

    // مواقع أخرى: الرابط كما هو، أو لا شيء إن أُوقفت الصور الخارجية
    expect(PdfBuilder::src('https://example.com/a.jpg'))->toBe('https://example.com/a.jpg');
    config(['alnajat.pdf.remote_images' => false]);
    expect(PdfBuilder::src('https://example.com/a.jpg'))->toBeNull();

    $html = PdfBuilder::images('<p>نص<img src="upload/not-here.jpg" alt="x"> و<img alt="y" src="'.url('index.php').'"></p>');
    expect($html)->not->toContain('not-here')
        ->and($html)->toContain('src="'.public_path('index.php').'"')
        ->and($html)->toContain('alt="y"');
});
