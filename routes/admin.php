<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\MissingLinkController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\NewsOrderController;
use App\Http\Controllers\Admin\NewspaperController;
use App\Http\Controllers\Admin\PdfTemplateController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\PublicationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\PreventDuringImpersonation;
use Illuminate\Support\Facades\Route;

/*
 * لوحة التحكم: /cp (نفس المسار القديم). الاسم admin.*
 * مُحمّل من bootstrap/app.php داخل مجموعة web.
 *
 * كل مسار محمي بصلاحيته من config/permissions.php (can:الوحدة.العملية).
 */

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

/**
 * مسارات CRUD بصلاحية لكل عملية:
 * index → view، create/store → create، edit/update → update، destroy → delete، toggle → publish.
 * $own: يمرّر السجل للـ Gate (الأخبار: العضو بلا manage_all يعدّل أخباره فقط).
 */
$crud = function (string $name, string $controller, string $param, array $only = ['index', 'create', 'store', 'edit', 'update', 'destroy', 'toggle'], bool $own = false, ?string $permission = null) {
    $record = $own ? ",$param" : '';
    $can = 'can:'.($permission ?? $name);
    $routes = [
        'index' => fn () => Route::get($name, [$controller, 'index'])->middleware("$can.view"),
        'create' => fn () => Route::get("$name/create", [$controller, 'create'])->middleware("$can.create"),
        'store' => fn () => Route::post($name, [$controller, 'store'])->middleware("$can.create"),
        'edit' => fn () => Route::get("$name/{{$param}}/edit", [$controller, 'edit'])->middleware("$can.update$record"),
        'update' => fn () => Route::match(['put', 'patch'], "$name/{{$param}}", [$controller, 'update'])->middleware("$can.update$record"),
        'destroy' => fn () => Route::delete("$name/{{$param}}", [$controller, 'destroy'])->middleware("$can.delete$record"),
        'toggle' => fn () => Route::patch("$name/{{$param}}/toggle", [$controller, 'toggle'])->middleware("$can.publish$record"),
    ];

    foreach ($only as $action) {
        $routes[$action]()->name("$name.$action");
    }
};

Route::middleware(['auth', 'active'])->group(function () use ($crud) {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // الملف الشخصي: لكل عضو مسجّل، بلا صلاحية خاصة
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password')->middleware(['throttle:6,1', PreventDuringImpersonation::class]);
    Route::delete('profile/sessions', [ProfileController::class, 'logoutOthers'])->name('profile.sessions.destroy')->middleware(['throttle:6,1', PreventDuringImpersonation::class]);

    // الدخول بحساب عضو بلا كلمة مرور (users.impersonate)، والعودة للحساب الأصلي (لمن دخل بحساب غيره فقط)
    Route::post('users/{user}/impersonate', [ImpersonationController::class, 'store'])->name('users.impersonate')->middleware(['can:users.impersonate', 'throttle:20,1']);
    Route::delete('impersonate', [ImpersonationController::class, 'destroy'])->name('impersonate.leave');

    // ترتيب أخبار اليوم (قبل مسارات الخبر حتى لا يُفهم «order» كرقم خبر)
    Route::get('news/order', [NewsOrderController::class, 'index'])->name('news.order')->middleware('can:news.order');
    Route::put('news/order', [NewsOrderController::class, 'update'])->name('news.order.update')->middleware('can:news.order');

    // معاينة قالب النشرة قبل الحفظ
    Route::get('publications/preview', [PublicationController::class, 'preview'])->name('publications.preview')->middleware('can:publications.view');

    // المحتوى
    $crud('news', NewsController::class, 'news', own: true);
    $crud('publications', PublicationController::class, 'publication');
    $crud('categories', CategoryController::class, 'category');
    $crud('newspapers', NewspaperController::class, 'newspaper');
    $crud('banners', BannerController::class, 'banner');
    $crud('uploads', UploadController::class, 'upload', ['index', 'store', 'destroy']);

    // قوالب النشرة (المحرر بالسحب والإفلات)؛ صفحة التعديل تفتح للقراءة لمن يملك العرض فقط
    Route::get('pdf-templates/fonts/{font}', [SettingController::class, 'font'])->where('font', '[a-z0-9-]+')->name('pdf-templates.font')->middleware('can:pdf_templates.view');
    $crud('pdf-templates', PdfTemplateController::class, 'pdfTemplate', ['index', 'create', 'store', 'update', 'destroy'], permission: 'pdf_templates');
    Route::prefix('pdf-templates/{pdfTemplate}')->name('pdf-templates.')->group(function () {
        Route::get('edit', [PdfTemplateController::class, 'edit'])->name('edit')->middleware('can:pdf_templates.view');
        Route::post('preview', [PdfTemplateController::class, 'preview'])->name('preview')->middleware('can:pdf_templates.view');
        Route::post('images', [PdfTemplateController::class, 'upload'])->name('upload')->middleware('can:pdf_templates.update');
        Route::post('duplicate', [PdfTemplateController::class, 'duplicate'])->name('duplicate')->middleware('can:pdf_templates.create');
        Route::patch('publish', [PdfTemplateController::class, 'publish'])->name('publish')->middleware('can:pdf_templates.publish');
        Route::patch('default', [PdfTemplateController::class, 'makeDefault'])->name('default')->middleware('can:pdf_templates.publish');
    });

    // النظام
    $crud('users', UserController::class, 'user', ['index', 'create', 'store', 'edit', 'update', 'destroy']);
    $crud('roles', RoleController::class, 'role', ['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // الإعدادات: كل تبويب بصلاحيته، يفحصها SettingController
    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('settings/preview', [SettingController::class, 'preview'])->name('settings.preview');
    Route::get('settings/fonts/{font}', [SettingController::class, 'font'])->where('font', '[a-z0-9-]+')->name('settings.font')->middleware('can:settings.pdf');

    Route::get('missing-links', [MissingLinkController::class, 'index'])->name('missing-links.index')->middleware('can:missing_links.view');
    Route::delete('missing-links', [MissingLinkController::class, 'clear'])->name('missing-links.clear')->middleware('can:missing_links.delete');
    Route::delete('missing-links/{missingLink}', [MissingLinkController::class, 'destroy'])->name('missing-links.destroy')->middleware('can:missing_links.delete');
});

// روابط اللوحة القديمة (cp/index.php?action=...) تذهب إلى الرئيسية الجديدة.
Route::get('index.php', fn () => redirect()->route('admin.dashboard', status: 301));
