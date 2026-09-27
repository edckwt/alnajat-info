<?php

use App\Models\Publication;
use App\Models\User;
use App\Services\PdfBuilder;
use App\Support\PdfDesign;
use App\Services\PdfTemplates;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->actingAs(User::forceCreate(['name' => 'محرر', 'username' => 'editor', 'email' => 'e@example.com',
        'password' => 'secret-123', 'role' => 'editor', 'is_active' => true]));

    $this->publication = Publication::forceCreate(['title' => 'نشرة', 'publication_date' => '2026-09-20', 'is_active' => true, 'pdf_version' => 3]);
});

it('reads each template cover, section page and last page from its css', function () {
    $dir = sys_get_temp_dir().'/alnajat-tpl-'.uniqid();
    File::ensureDirectoryExists("$dir/css");
    File::ensureDirectoryExists("$dir/images/v1");
    File::ensureDirectoryExists("$dir/images/v2");
    foreach (['v1/cover.jpg', 'v1/pdf-section-magazine.jpg', 'v1/pdf-footer-last-page.png', 'v2/first.jpg'] as $image) {
        File::put("$dir/images/$image", 'x');
    }
    File::put("$dir/css/pdf-v1.css", ".first_page {\n\theight: 1124px;\n\tbackground: #eee url({site_url}images/v1/cover.jpg?v=29) no-repeat;\n}\n.magazine_page {\n\tbackground: url({site_url}images/v1/pdf-section-magazine.jpg?v=11);\n}\n");
    // v2 يستعمل خلفية أقسام v1 كما في الملفات الحقيقية
    File::put("$dir/css/pdf-v2.css", ".first_page {\n\tbackground: url({site_url}images/v2/first.jpg);\n}\n.magazine_page {\n\tbackground: url({site_url}images/v1/pdf-section-magazine.jpg);\n}\n");

    config(['alnajat.pdf_latest_version' => 2]);
    Publication::forceCreate(['title' => 'قديمة', 'publication_date' => '2021-12-05', 'is_active' => true, 'pdf_version' => 1]);

    try {
        $templates = (new PdfTemplates($dir))->all();
    } finally {
        File::deleteDirectory($dir);
    }

    expect($templates[1])->toMatchArray([
        'cover' => 'images/v1/cover.jpg', 'section' => 'images/v1/pdf-section-magazine.jpg',
        'last' => 'images/v1/pdf-footer-last-page.png', 'count' => 1, 'from' => '2021-12', 'latest' => false,
    ])->and($templates[2])->toMatchArray([
        'cover' => 'images/v2/first.jpg', 'section' => 'images/v1/pdf-section-magazine.jpg', 'last' => null, 'latest' => true,
    ]);
});

it('shows the template picker on the publication form', function () {
    $this->get(route('admin.publications.edit', $this->publication))
        ->assertOk()
        ->assertSee('قالب الـ PDF')
        ->assertSee('القالب الثالث')
        ->assertSee('name="pdf_version" value="3"', false)
        ->assertSee('data-pdf-option="v1"', false)
        ->assertSee('معاينة PDF');

    $this->get(route('admin.publications.create'))->assertOk()->assertSee('القالب الخامس');
});

it('previews a publication with another template and the unsaved date', function () {
    $this->app->instance(PdfBuilder::class, new class extends PdfBuilder
    {
        public function publication(Publication $publication, ?int $version = null, ?int $sample = null, ?PdfDesign $design = null): string
        {
            return '%PDF '.($publication->id ?? 'new').' '.$publication->publication_date->toDateString()." v{$version} s{$sample}";
        }
    });

    $this->get(route('admin.publications.preview', ['publication' => $this->publication->id, 'version' => 5, 'date' => '2026-09-21']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertContent('%PDF '.$this->publication->id.' 2026-09-21 v5 s3');

    // لم يُحفظ شيء
    expect($this->publication->fresh()->pdf_version)->toBe(3)
        ->and($this->publication->fresh()->publication_date->toDateString())->toBe('2026-09-20');

    // نشرة جديدة لم تُحفظ بعد
    $this->get(route('admin.publications.preview', ['version' => 1, 'date' => '2026-09-22']))
        ->assertOk()->assertContent('%PDF new 2026-09-22 v1 s3');

    $this->get(route('admin.publications.preview', ['version' => 99]))->assertNotFound();
});
