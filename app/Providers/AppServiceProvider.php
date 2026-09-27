<?php

namespace App\Providers;

use App\Auth\LegacyAwareUserProvider;
use App\Legacy\LegacyTransform;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeBox;
use App\Models\News;
use App\Models\Newspaper;
use App\Models\PdfTemplate;
use App\Models\Publication;
use App\Models\Setting;
use App\Services\PdfCache;
use App\Support\Permissions;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LegacyTransform::class, fn ($app) => new LegacyTransform(
            legacyHosts: config('alnajat.legacy_hosts', []),
            pdfVersions: config('alnajat.pdf_versions', []),
            pdfLatestVersion: (int) config('alnajat.pdf_latest_version', 5),
            timezone: config('app.timezone', 'Asia/Kuwait'),
        ));
    }

    public function boot(): void
    {
        Auth::provider('eloquent-legacy', fn ($app, array $config) => new LegacyAwareUserProvider(
            $app['hash'],
            $config['model'],
        ));

        // مدير النظام فقط (مثل منح دور «مدير النظام» لغيره).
        Gate::define('admin', fn ($user) => $user->isAdmin());

        // كل صلاحية في config/permissions.php هي Gate باسمها: @can('news.create')، can:news.update,news
        Gate::before(function ($user, string $ability, array $arguments = []) {
            if (! Permissions::exists($ability)) {
                return null;
            }

            if (! $user->hasPermission($ability)) {
                return false;
            }

            // بلا «تعديل أخبار الآخرين»: العضو يعدّل ويحذف وينشر أخباره فقط.
            $record = $arguments[0] ?? null;
            if ($record instanceof News && in_array($ability, ['news.update', 'news.delete', 'news.publish'], true)
                && ! $user->hasPermission('news.manage_all') && (int) $record->created_by !== (int) $user->id) {
                return false;
            }

            return true;
        });

        // عربي دائماً (حتى لو كان APP_LOCALE=en في .env): النصوص والتواريخ ورسائل التحقق
        app()->setLocale('ar');

        // بيانات إضافية لقوالب الواجهة الجديدة (لا تعمل في الكلاسيكية)
        \App\Support\Theme::registerComposers();
        Carbon::setLocale('ar');

        Model::preventLazyLoading(! $this->app->isProduction());

        $this->invalidatePdfOnChanges();
    }

    /**
     * أي تعديل يغيّر محتوى ملف PDF يُسقط نسخته الجاهزة.
     * (increment للمشاهدات لا يطلق saved، فلا يؤثر.)
     */
    private function invalidatePdfOnChanges(): void
    {
        $pdf = fn (): PdfCache => $this->app->make(PdfCache::class);

        $news = function (News $news) use ($pdf) {
            $pdf()->forgetNews($news->id);
            $pdf()->forgetDate($news->published_date);

            $previous = $news->getPrevious()['published_date'] ?? null;
            if ($previous) {
                $pdf()->forgetDate($previous);
            }
        };
        News::saved($news);
        News::deleted($news);

        $publication = fn (Publication $p) => $pdf()->forgetPublication($p->id);
        Publication::saved($publication);
        Publication::deleted($publication);

        foreach ([Setting::class, HomeBox::class, Banner::class, Category::class, Newspaper::class, PdfTemplate::class] as $model) {
            $model::saved(fn () => $pdf()->flush());
            $model::deleted(fn () => $pdf()->flush());
        }
    }
}
