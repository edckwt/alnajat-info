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
