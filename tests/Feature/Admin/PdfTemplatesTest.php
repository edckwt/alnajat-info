<?php

use App\Models\Category;
use App\Models\HomeBox;
use App\Models\News;
use App\Models\PdfTemplate;
use App\Models\Publication;
use App\Models\User;
use App\Services\ImageUploader;
use App\Services\PdfBuilder;
use App\Services\PdfTemplateFactory;
use App\Services\PdfTemplates;
use App\Support\PdfDesign;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->admin = User::forceCreate(['name' => 'المدير', 'username' => 'admin', 'email' => 'admin@example.com',
        'password' => 'secret-123', 'role' => 'admin', 'is_active' => true]);
    $this->actingAs($this->admin);

    // ملفات القوالب القديمة في مجلد مؤقت (لا يعتمد الاختبار على public/css الحقيقي)
    $this->public = sys_get_temp_dir().'/alnajat-tpl-'.uniqid();
    File::ensureDirectoryExists("{$this->public}/css");
    File::ensureDirectoryExists("{$this->public}/images/v5");
    foreach (['first.jpg', 'pdf-section-magazine.jpg', 'pdf-section-radio.jpg', 'pdf-footer-bg.png', 'pdf-footer-last-page.png', 'archive-ar-2.png'] as $image) {
        File::put("{$this->public}/images/v5/$image", 'x');
    }
    File::put("{$this->public}/css/pdf-v5.css", implode("\n", [
        ".first_page {\n\tbackground: #eee url({site_url}images/v5/first.jpg?v=4) no-repeat top center;\n}",
        ".magazine_page {\n\tbackground: #fff url({site_url}images/v5/pdf-section-magazine.jpg?v=4) no-repeat top center;\n}",
        ".radio_page {\n\tbackground: #fff url({site_url}images/v5/pdf-section-radio.jpg?v=4) no-repeat top center;\n}",
        ".pdf_footer {\n\theight: 80px;\n\tbackground: url({site_url}images/v5/pdf-footer-bg.png?v=22) no-repeat top center;\n}",
    ]));
    $this->app->instance(PdfTemplates::class, new PdfTemplates($this->public));

    $this->uploads = sys_get_temp_dir().'/alnajat-up-'.uniqid();
    $this->app->instance(ImageUploader::class, new ImageUploader($this->uploads));
});

afterEach(function () {
    File::deleteDirectory($this->public);
    File::deleteDirectory($this->uploads);
});

it('cleans any design json into a safe one', function () {
    $design = PdfDesign::fromArray([
        'page' => ['format' => 'B9', 'orientation' => 'X'],
        'pages' => ['cover' => ['background' => ['image' => '../../.env', 'color' => 'red'], 'elements' => [
            ['type' => 'script', 'x' => 1],
            ['type' => 'text', 'x' => 9999, 'text' => 'مرحبا {{site_title}}', 'style' => ['color' => 'javascript:x', 'size' => 1000]],
            ['type' => 'image', 'src' => 'https://evil.test/x.png" onerror="x'],
        ]]],
    ])->toArray();

    $cover = $design['pages']['cover'];
    expect($design['page'])->toMatchArray(['format' => 'A4', 'orientation' => 'P'])
        ->and($cover['background'])->toBe(['image' => null, 'color' => '#ffffff', 'fit' => 'fill'])
        ->and($cover['elements'])->toHaveCount(2)
        ->and($cover['elements'][0]['x'])->toBe(420.0)                 // محصور في حدود الصفحة
        ->and($cover['elements'][0]['style']['color'])->toBe('#13232e')
        ->and($cover['elements'][0]['style']['size'])->toBe(200.0)
        ->and($cover['elements'][1]['src'])->toBeNull()
        ->and($design['pages']['section']['content'])->toHaveKeys(['x', 'y', 'w', 'h']);
});

it('turns a design into mPDF pages, variables and fixed elements', function () {
    $design = PdfDesign::fromArray([
        'pages' => [
            'section' => [
                'content' => ['x' => 10, 'y' => 40, 'w' => 190, 'h' => 230],
                'categoryBackgrounds' => ['4' => 'images/v5/pdf-section-radio.jpg'],
                'elements' => [['type' => 'text', 'x' => 10, 'y' => 5, 'w' => 100, 'h' => 10, 'text' => '{{category_name}} — {{page}}/{{pages}}', 'link' => '{{archive_url}}']],
            ],
        ],
    ]);

    expect($design->pageCss())
        ->toContain('@page section { margin-top: 40mm; margin-right: 10mm; margin-bottom: 27mm; margin-left: 10mm;')
        ->toContain('@page section_c4')
        ->and($design->sectionSelector(4))->toBe('section_c4')
        ->and($design->sectionSelector(1))->toBe('section');

    $html = $design->elementsHtml('section', ['category_name' => 'الإذاعة', 'archive_url' => 'https://site.test/archive.html']);
    expect($html)->toContain('position: fixed; left: 10mm; top: 5mm;')
        ->toContain('الإذاعة — {PAGENO}/{nbpg}')
        ->toContain('<a href="https://site.test/archive.html"');
});

it('lists legacy and designed templates', function () {
    PdfTemplate::forceCreate(['name' => 'قالب الشتاء', 'status' => 'published', 'design' => app(PdfTemplateFactory::class)->blank()]);

    $this->get(route('admin.pdf-templates.index'))->assertOk()
        ->assertSee('القوالب المصمَّمة')->assertSee('قالب الشتاء')->assertSee('معتمد')
        ->assertSee('القوالب القديمة')->assertSee('إنشاء قالب مصمَّم منه');
});

it('creates a draft from a legacy template with its real images', function () {
    $this->post(route('admin.pdf-templates.store'), ['name' => 'الخامس المصمَّم', 'from' => 'v5'])->assertRedirect();

    $template = PdfTemplate::sole();
    $design = $template->design;

    expect($template->status)->toBe('draft')
        ->and($template->based_on_version)->toBe(5)
        ->and($design['pages']['cover']['background']['image'])->toBe('images/v5/first.jpg')
        ->and($design['pages']['section']['background']['image'])->toBe('images/v5/pdf-section-magazine.jpg')
        ->and($design['pages']['section']['categoryBackgrounds'])->toBe(['4' => 'images/v5/pdf-section-radio.jpg'])
        ->and(collect($design['pages']['section']['elements'])->pluck('text')->filter()->values()->all())->toContain('{{category_name}}', '{{page}}');

    $this->get(route('admin.pdf-templates.edit', $template))->assertOk()
        ->assertSee('data-designer', false)->assertSee('الصفحة المتكررة')->assertSee('images\/v5\/first.jpg', false);
});

it('saves the editor json, and locks the template once approved', function () {
    $this->post(route('admin.pdf-templates.store'), ['name' => 'جديد', 'from' => 'blank']);
    $template = PdfTemplate::sole();
    $design = $template->design;
    $design['pages']['cover']['elements'][] = ['id' => 'x1', 'type' => 'rect', 'x' => 5, 'y' => 5, 'w' => 20, 'h' => 20, 'style' => ['bg' => '#ff0000']];

    $this->putJson(route('admin.pdf-templates.update', $template), ['name' => 'جديد ٢', 'design' => $design])
        ->assertOk()->assertJsonPath('ok', true);

    expect($template->fresh()->name)->toBe('جديد ٢')
        ->and(collect($template->fresh()->design['pages']['cover']['elements'])->firstWhere('id', 'x1')['style']['bg'])->toBe('#ff0000');

    $this->patch(route('admin.pdf-templates.publish', $template))->assertRedirect();
    expect($template->fresh()->isPublished())->toBeTrue();

    $this->putJson(route('admin.pdf-templates.update', $template), ['name' => 'تعديل', 'design' => $design])->assertStatus(409);

    // النسخة مسودة قابلة للتعديل
    $this->post(route('admin.pdf-templates.duplicate', $template))->assertRedirect();
    expect(PdfTemplate::where('status', 'draft')->where('based_on_id', $template->id)->exists())->toBeTrue();
});

it('makes an approved template the default for new publications', function () {
    Category::forceCreate(['id' => 1, 'name' => 'محليات', 'is_active' => true]);
    $template = PdfTemplate::forceCreate(['name' => 'الافتراضي', 'status' => 'published', 'design' => app(PdfTemplateFactory::class)->blank()]);

    $this->patch(route('admin.pdf-templates.default', $template))->assertRedirect();
    expect($template->fresh()->is_default)->toBeTrue();

    // خبر بتاريخ جديد ينشئ نشرته بالقالب الافتراضي
    $this->post(route('admin.news.store'), ['type' => 1, 'title' => 'خبر', 'published_date' => '2026-09-26', 'is_active' => '1', 'categories' => [1]]);
    expect(Publication::where('publication_date', '2026-09-26')->sole()->pdf_template_id)->toBe($template->id);

    // وفي نموذج النشرة يظهر بين الخيارات
    $this->get(route('admin.publications.create'))->assertOk()
        ->assertSee('data-pdf-option="t'.$template->id.'"', false)
        ->assertSee('name="pdf_template_id" value="'.$template->id.'"', false);

    // لا يُحذف القالب الافتراضي أو المستخدم
    $this->delete(route('admin.pdf-templates.destroy', $template))->assertSessionHasErrors('template');
});

it('previews the unsaved design and uploads dropped images', function () {
    $this->post(route('admin.pdf-templates.store'), ['name' => 'معاينة', 'from' => 'blank']);
    $template = PdfTemplate::sole();

    $this->app->instance(PdfBuilder::class, new class extends PdfBuilder
    {
        public function publication(Publication $publication, ?int $version = null, ?int $sample = null, ?PdfDesign $design = null): string
        {
            return '%PDF design='.($design ? count($design->page('cover')['elements']) : 'none').' sample='.$sample;
        }
    });

    $design = $template->design;
    $design['pages']['cover']['elements'] = [];
    $this->postJson(route('admin.pdf-templates.preview', $template), ['design' => $design])
        ->assertOk()->assertHeader('Content-Type', 'application/pdf')
        ->assertContent('%PDF design=0 sample=3');

    $this->post(route('admin.pdf-templates.upload', $template), ['image' => UploadedFile::fake()->image('bg.jpg', 1240, 1754)], ['Accept' => 'application/json'])
        ->assertOk()->assertJsonPath('path', fn ($path) => str_starts_with($path, 'upload/pdf-template_'));
});

it('lets editors view templates but not change them', function () {
    $editor = User::forceCreate(['name' => 'محرر', 'username' => 'editor', 'email' => 'e@example.com',
        'password' => 'secret-123', 'role' => 'editor', 'is_active' => true]);
    $template = PdfTemplate::forceCreate(['name' => 'قالب', 'status' => 'draft', 'design' => app(PdfTemplateFactory::class)->blank()]);

    $this->actingAs($editor);
    $this->get(route('admin.pdf-templates.index'))->assertOk();
    $this->get(route('admin.pdf-templates.edit', $template))->assertOk()->assertSee('"readonly":true', false);
    $this->get(route('admin.pdf-templates.create'))->assertForbidden();
    $this->putJson(route('admin.pdf-templates.update', $template), ['name' => 'x', 'design' => []])->assertForbidden();
    $this->patch(route('admin.pdf-templates.publish', $template))->assertForbidden();
});

it('really renders a designed publication with mPDF', function () {
    Category::forceCreate(['id' => 1, 'name' => 'محليات', 'is_active' => true]);
    HomeBox::forceCreate(['context' => 'pdf', 'position' => 1, 'category_id' => 1, 'items_limit' => 2, 'type' => 5]);
    $publication = Publication::forceCreate(['title' => 'نشرة', 'publication_date' => '2026-09-25', 'is_active' => true]);
    News::forceCreate(['title' => 'خبر النشرة', 'description' => 'وصف', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25'])
        ->categories()->attach(1);

    $template = PdfTemplate::forceCreate(['name' => 'حقيقي', 'status' => 'published', 'design' => app(PdfTemplateFactory::class)->blank()]);
    $publication->update(['pdf_template_id' => $template->id]);

    $pdf = app(PdfBuilder::class)->publication($publication->fresh());

    expect($pdf)->toStartWith('%PDF')
        ->and(substr_count($pdf, '/Type /Page'))->toBeGreaterThanOrEqual(3); // غلاف + صفحة خبر + ختام
})->skip(fn () => ! class_exists(\Mpdf\Mpdf::class), 'mPDF غير مثبت');

it('warns when template images are missing on this server', function () {
    $root = sys_get_temp_dir().'/alnajat-up-root-'.uniqid();
    File::ensureDirectoryExists($root);
    File::put($root.'/here.png', 'x');
    config(['alnajat.uploads.root' => $root]);

    $template = PdfTemplate::forceCreate(['name' => 'قالب الخادم', 'status' => 'draft', 'design' => PdfDesign::fromArray([
        'pages' => [
            'cover' => ['background' => ['image' => 'upload/not-on-server.jpg'], 'elements' => [['type' => 'image', 'src' => 'upload/here.png', 'x' => 1, 'y' => 1, 'w' => 10, 'h' => 10]]],
            'section' => ['categoryBackgrounds' => ['1' => 'images/v9/missing-section.jpg']],
        ],
    ])->toArray()]);

    expect($template->toDesign()->imagePaths())->toEqualCanonicalizing(['upload/not-on-server.jpg', 'upload/here.png', 'images/v9/missing-section.jpg'])
        ->and(array_keys(\App\Support\AssetCheck::missingTemplateImages([$template])))
        ->toEqualCanonicalizing(['upload/not-on-server.jpg', 'images/v9/missing-section.jpg']);

    $this->get(route('admin.pdf-templates.index'))->assertOk()
        ->assertSee('data-assets-alert', false)
        ->assertSee('upload/not-on-server.jpg')
        ->assertSee('alnajat:assets --copy');

    $this->get(route('admin.pdf-templates.edit', $template))->assertOk()->assertSee('upload/not-on-server.jpg');

    File::deleteDirectory($root);
});
