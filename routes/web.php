<?php

use App\Http\Controllers\Site\CategoryController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\NewsController;
use App\Http\Controllers\Site\PdfController;
use App\Http\Controllers\Site\PublicationController;
use App\Http\Controllers\Site\SearchController;
use App\Http\Middleware\ApplySiteTheme;
use App\Http\Middleware\EnsureSiteIsOpen;
use App\Http\Middleware\RedirectLegacyUrls;
use App\Support\Media;
use Illuminate\Support\Facades\Route;

/*
 * الموقع العام بنفس روابط الموقع القديم (.htaccess):
 *   show/{id}  pdf-show/{id}  category/{id}  search/{q}  publication/{id}
 *   pdf-publication/{id}  archive.html  today-news.html
 * الواجهة (الكلاسيكية أو الجديدة) تُطبَّق قبل كل شيء حتى تظهر صفحة الإغلاق بها أيضاً.
 */
Route::middleware([ApplySiteTheme::class, EnsureSiteIsOpen::class])->group(function () {
    Route::get('/', HomeController::class)->middleware(RedirectLegacyUrls::class)->name('home');

    Route::get('show/{id}', [NewsController::class, 'show'])->whereNumber('id')->name('news.show');
    Route::get('category/{id}', [CategoryController::class, 'show'])->whereNumber('id')->name('category.show');
    Route::get('search/{q?}', SearchController::class)->where('q', '.*')->name('search');
    Route::get('publication/{id}', [PublicationController::class, 'show'])->whereNumber('id')->name('publication.show');
    Route::get('archive.html', [PublicationController::class, 'index'])->name('publications.index');

    Route::get('pdf-show/{id}', [PdfController::class, 'news'])->whereNumber('id')->name('news.pdf');
    Route::get('pdf-publication/{id}', [PdfController::class, 'publication'])->whereNumber('id')->name('publication.pdf');
    Route::get('today-news.html', [PdfController::class, 'today'])->name('pdf.today');
});

/*
 * الروابط القديمة للصور والملفات /upload/… (المواقع التي نقلت عنا، ونصوص الأخبار، والـ PDF القديمة)
 * بعد نقل المجلد إلى storage/app/public/upload: يُقدَّم الملف نفسه من المجلد الجديد (200) بلا تحويل.
 * التحويل 301 كان يكسر قراءة الصورة بـ JavaScript (تحرير الصورة الحالية) إن اختلف أصل الرابط بعد التحويل
 * (http/https أو www خلف وكيل): «Cross-Origin Request Blocked … Status code: 301».
 * على الخادم يقدّمها nginx مباشرة (alias في docs/DEPLOY.md)، وهذا المسار احتياط (MAMP، artisan serve).
 */
if (config('alnajat.uploads.legacy_redirect', true) && Media::baseUrl() !== 'upload') {
    Route::get('upload/{path}', function (string $path) {
        $file = Media::path(Media::PREFIX.$path);
        abort_unless($file !== null && is_file($file) && preg_match('/\.(jpe?g|png|gif|webp|bmp|ico|svg|pdf|docx?|mp3|m4a|wav|ogg|mp4|webm|mov)$/i', $file), 404);

        $headers = ['Cache-Control' => 'public, max-age=2592000'];
        if (str_ends_with(strtolower($file), '.svg')) {
            $headers['Content-Security-Policy'] = "default-src 'none'; style-src 'unsafe-inline'"; // لا سكربت داخل SVG
        }

        return response()->file($file, $headers);
    })->where('path', '.+')->name('uploads.legacy');
}
