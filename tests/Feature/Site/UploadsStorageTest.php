<?php

use App\Models\News;
use App\Models\Upload;
use App\Models\User;
use App\Services\ImageUploader;
use App\Services\PdfBuilder;
use App\Support\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

/*
 * الصور المرفوعة في storage/app/public/upload (تُعرض من /storage/upload/…) مع بقاء القيم في القاعدة upload/….
 * مجلد الرفع في الاختبارات مؤقت (alnajat.uploads.root) حتى لا يُمس مجلد المشروع.
 */
beforeEach(function () {
    $this->root = sys_get_temp_dir().'/alnajat-uploads-'.uniqid();
    File::ensureDirectoryExists($this->root.'/thumbs');
    config(['alnajat.uploads.root' => $this->root]);
});

afterEach(fn () => File::deleteDirectory($this->root));

it('uses storage/app/public/upload and /storage/upload by default', function () {
    if (env('UPLOADS_ROOT') || env('UPLOADS_URL')) {
        $this->markTestSkipped('مجلد الرفع مخصص في .env');
    }
    $defaults = (require config_path('alnajat.php'))['uploads'];

    expect($defaults['root'])->toBe(storage_path('app/public/upload'))
        ->and($defaults['url'])->toBe('storage/upload');
});

it('maps stored upload/ paths to the storage link and keeps other paths', function () {
    expect(Media::url('upload/a.jpg'))->toBe(asset('storage/upload/a.jpg'))
        ->and(Media::url('/upload/a.jpg'))->toBe(asset('storage/upload/a.jpg'))
        ->and(Media::url('images/logo.png'))->toBe(asset('images/logo.png'))
        ->and(Media::url('https://example.com/upload/a.jpg'))->toBe('https://example.com/upload/a.jpg')
        ->and(Media::url(''))->toBeNull()
        ->and(Media::path('upload/a.jpg'))->toBe($this->root.'/a.jpg')
        ->and(Media::path('upload/../../.env'))->toBeNull()
        ->and(Media::path('images/logo.png'))->toBeNull()
        ->and(Media::isUpload('upload/a.jpg'))->toBeTrue();

    // الرجوع إلى المجلد القديم بالإعداد فقط
    config(['alnajat.uploads.url' => 'upload']);
    expect(Media::url('upload/a.jpg'))->toBe(asset('upload/a.jpg'));
});

it('finds thumbnails in the new upload folder', function () {
    expect(Media::thumb('upload/a.jpg'))->toBe(asset('storage/upload/a.jpg')); // لا مصغّر: الأصل

    File::put($this->root.'/thumbs/a_350x155.jpg', 'x');
    expect(Media::thumb('upload/a.jpg'))->toBe(asset('storage/upload/thumbs/a_350x155.jpg'))
        ->and(Media::thumb('upload/a.jpg', 'large'))->toBe(asset('storage/upload/a.jpg'));
});

it('saves new uploads and their thumbnails in the upload folder', function () {
    $path = (new ImageUploader)->store(UploadedFile::fake()->image('a.jpg', 400, 300), 'news');
    $name = pathinfo($path, PATHINFO_FILENAME);

    expect($path)->toStartWith('upload/news_')
        ->and(Media::path($path))->toBe($this->root.'/'.$name.'.jpg')
        ->and(File::exists($this->root.'/'.$name.'.jpg'))->toBeTrue()
        ->and(File::exists($this->root.'/thumbs/'.$name.'_350x155.jpg'))->toBeTrue()
        ->and(File::exists($this->root.'/'.$name.'_thumbnail.jpg'))->toBeTrue()
        ->and(File::exists(public_path($path)))->toBeFalse();
});

it('deletes a library file with its thumbnails from the upload folder', function () {
    $admin = User::forceCreate(['name' => 'المدير', 'username' => 'boss', 'email' => 'boss@example.com',
        'password' => 'secret-123', 'role' => 'admin', 'is_active' => true]);
    foreach (['upload_1.jpg', 'upload_1_thumbnail.jpg', 'thumbs/upload_1_350x155.jpg', 'keep.jpg'] as $file) {
        File::put($this->root.'/'.$file, 'x');
    }
    $upload = Upload::forceCreate(['title' => 'صورة', 'path' => 'upload/upload_1.jpg', 'size' => 1, 'extension' => 'jpg', 'mime' => 'image/jpeg', 'user_id' => $admin->id]);

    $this->actingAs($admin)->delete(route('admin.uploads.destroy', $upload))->assertRedirect();

    expect(Upload::count())->toBe(0)
        ->and(File::exists($this->root.'/upload_1.jpg'))->toBeFalse()
        ->and(File::exists($this->root.'/upload_1_thumbnail.jpg'))->toBeFalse()
        ->and(File::exists($this->root.'/thumbs/upload_1_350x155.jpg'))->toBeFalse()
        ->and(File::exists($this->root.'/keep.jpg'))->toBeTrue();
});

it('reads upload images for the PDF from the upload folder in every link form', function () {
    File::put($this->root.'/p.jpg', 'x');
    $local = $this->root.'/p.jpg';

    expect(PdfBuilder::src('upload/p.jpg'))->toBe($local)
        ->and(PdfBuilder::src(url('storage/upload/p.jpg')))->toBe($local)
        ->and(PdfBuilder::src(url('upload/p.jpg')))->toBe($local)
        ->and(PdfBuilder::src('https://www.alnajat.info/upload/p.jpg?v=2'))->toBe($local)
        ->and(PdfBuilder::src('upload/missing.jpg'))->toBeNull();
});

it('redirects old /upload links permanently to the new place', function () {
    $this->get('/upload/2019/news_1.png')
        ->assertStatus(301)
        ->assertRedirect(asset('storage/upload/2019/news_1.png'));
});

it('copies the old upload folder with a dry run first, skipping executable files', function () {
    $source = sys_get_temp_dir().'/alnajat-old-upload-'.uniqid();
    File::ensureDirectoryExists($source.'/thumbs');
    File::put($source.'/a.jpg', 'image-a');
    File::put($source.'/thumbs/a_350x155.jpg', 'thumb');
    File::put($source.'/shell.php', '<?php echo 1;');
    File::put($source.'/.htaccess', 'deny');
    News::forceCreate(['title' => 'خبر', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25', 'image' => 'upload/a.jpg']);

    try {
        $this->artisan('alnajat:move-uploads', ['--from' => $source, '--dry-run' => true])->assertSuccessful();
        expect(File::exists($this->root.'/a.jpg'))->toBeFalse();

        $this->artisan('alnajat:move-uploads', ['--from' => $source])->assertSuccessful();
        expect(File::get($this->root.'/a.jpg'))->toBe('image-a')
            ->and(File::exists($this->root.'/thumbs/a_350x155.jpg'))->toBeTrue()
            ->and(File::exists($this->root.'/shell.php'))->toBeFalse()
            ->and(File::exists($this->root.'/.htaccess'))->toBeFalse()
            ->and(File::exists($source.'/a.jpg'))->toBeTrue(); // النسخ لا يمس المصدر

        // إعادة التشغيل آمنة، وملف بحجم مختلف في الوجهة تعارض لا يُستبدل إلا بـ --force
        File::put($this->root.'/a.jpg', 'changed-in-new-site');
        $this->artisan('alnajat:move-uploads', ['--from' => $source])->assertFailed();
        expect(File::get($this->root.'/a.jpg'))->toBe('changed-in-new-site');

        $this->artisan('alnajat:move-uploads', ['--from' => $source, '--force' => true])->assertSuccessful();
        expect(File::get($this->root.'/a.jpg'))->toBe('image-a');
    } finally {
        File::deleteDirectory($source);
    }
});

it('moves files out of the source only with --move and a confirmation', function () {
    $source = sys_get_temp_dir().'/alnajat-old-upload-'.uniqid();
    File::ensureDirectoryExists($source);
    File::put($source.'/b.jpg', 'image-b');

    try {
        $this->artisan('alnajat:move-uploads', ['--from' => $source, '--move' => true])
            ->expectsConfirmation('سيُحذف كل ملف من المصدر بعد نسخه (والمصدر قد يكون مجلد الموقع القديم). متابعة؟', 'yes')
            ->assertSuccessful();

        expect(File::get($this->root.'/b.jpg'))->toBe('image-b')
            ->and(File::exists($source.'/b.jpg'))->toBeFalse();
    } finally {
        File::deleteDirectory($source);
    }
});
