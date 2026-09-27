<?php

namespace App\Support;

use App\Models\Category;
use App\Models\HomeBox;
use App\Models\News;
use App\Models\PdfTemplate;
use App\Models\Publication;
use App\Models\Setting;
use App\Services\PdfBuilder;
use App\Services\PdfTemplateFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

/**
 * واجهات الموقع العام. «classic» هي قوالب resources/views/site كما هي (الموقع القديم)،
 * وأي واجهة أخرى مجلد في resources/views/themes/{الواجهة} يُقدَّم على المسار الأصلي:
 * ما يوجد فيه يُستخدم، وما لا يوجد يُؤخذ من الكلاسيكية. الرجوع للقديمة = اختيارها من الإعدادات.
 */
final class Theme
{
    public const DEFAULT = 'classic';

    /** مفتاح الجلسة لمعاينة واجهة غير المعتمدة (للمدير فقط). */
    public const PREVIEW_KEY = 'site_theme_preview';

    public const THEMES = [
        'classic' => [
            'name' => 'الواجهة الكلاسيكية',
            'hint' => 'تصميم الموقع القديم كما هو (Bootstrap وملف css/style.css).',
        ],
        'modern' => [
            'name' => 'الواجهة الجديدة',
            'hint' => 'نشرة اليوم أولاً، بطاقات واضحة، قراءة مريحة على الجوال، وأغلفة النشرات من قوالبها.',
        ],
    ];

    private static ?string $current = null;

    /** @var array<string, PdfDesign> */
    private static array $covers = [];

    public static function exists(?string $theme): bool
    {
        return is_string($theme) && isset(self::THEMES[$theme]);
    }

    /** الواجهة المعتمدة للزوار (من الإعدادات). */
    public static function active(): string
    {
        $theme = (string) Setting::get('site_theme');

        return self::exists($theme) ? $theme : self::DEFAULT;
    }

    /** الواجهة المطبّقة في هذا الطلب (قد تكون معاينة). */
    public static function current(): string
    {
        return self::$current ?? self::DEFAULT;
    }

    public static function is(string $theme): bool
    {
        return self::current() === $theme;
    }

    /** يوجّه view('site.…') إلى مجلد الواجهة أولاً. يُستدعى في كل طلب للموقع العام. */
    public static function apply(string $theme): void
    {
        $theme = self::exists($theme) ? $theme : self::DEFAULT;
        self::$current = $theme;

        $paths = (array) config('view.paths');
        if ($theme !== self::DEFAULT) {
            array_unshift($paths, resource_path('views/themes/'.$theme));
        }

        $finder = View::getFinder();
        $finder->setPaths($paths);
        $finder->flush();
    }

    /** مسار ملف ثابت للواجهة مع رقم نسخة (لتجاوز ذاكرة المتصفح بعد كل تعديل). */
    public static function asset(string $file): string
    {
        $relative = 'themes/'.self::current().'/'.ltrim($file, '/');
        $path = public_path($relative);

        return asset($relative).(is_file($path) ? '?v='.filemtime($path) : '');
    }

    /**
     * تصميم غلاف النشرة كما يُطبع في الـ PDF: قالبها المصمَّم، أو نسخة من قالبها القديم vN.
     * يُرسم في الواجهة الجديدة بدل صورة ثابتة، فيطابق الملف دائماً.
     */
    public static function coverDesign(Publication $publication): PdfDesign
    {
        $key = $publication->pdf_template_id ? 't'.$publication->pdf_template_id : 'v'.((int) $publication->pdf_version ?: (int) config('alnajat.pdf_latest_version'));

        return self::$covers[$key] ??= $publication->pdf_template_id && ($template = $publication->pdfTemplate ?? PdfTemplate::find($publication->pdf_template_id))
            ? $template->toDesign()
            : PdfDesign::fromArray(app(PdfTemplateFactory::class)->fromLegacy((int) substr($key, 1)));
    }

    /** @return array<string,string> متغيرات الغلاف ({{date_long}} …) لنشرة */
    public static function coverVariables(Publication $publication): array
    {
        return app(PdfBuilder::class)->designVariables($publication);
    }

    /** أقسام شريط التنقل: أقسام صناديق الرئيسية بترتيبها، ثم بقية الأقسام الفعّالة. */
    public static function navCategories(): Collection
    {
        return once(function () {
            $ordered = HomeBox::for(HomeBox::HOME)->whereNotNull('category_id')->pluck('category_id')->unique()->values();
            $categories = Category::where('is_active', true)->get(['id', 'name'])->keyBy('id');

            return $ordered->map(fn ($id) => $categories->get($id))->filter()
                ->concat($categories->except($ordered->all())->values())
                ->values();
        });
    }

    /**
     * تُسجَّل مرة لكل تطبيق من AppServiceProvider (لا من apply: العلم الثابت كان يبقى بين تطبيقات
     * الاختبارات فلا تُسجَّل في التطبيق الجديد).
     */
    public static function registerComposers(): void
    {
        self::$current = null; // تطبيق جديد (مهم في الاختبارات): لا تبقى واجهة طلب سابق

        // بيانات إضافية تحتاجها الواجهة الجديدة فقط؛ الكلاسيكية لا تتأثر ولا تُنفّذ استعلاماتها.
        View::composer('site.home', function ($view) {
            if (self::current() === self::DEFAULT) {
                return;
            }
            $view->with('recentPublications', Publication::published()->orderByDesc('publication_date')->orderByDesc('id')->limit(3)->get());
        });

        View::composer('site.news', function ($view) {
            if (self::current() === self::DEFAULT || ! ($news = $view->getData()['news'] ?? null)) {
                return;
            }
            $view->with([
                'sameDay' => $news->published_date
                    ? News::published()->with('newspaper:id,name,logo')
                        ->where('published_date', $news->published_date->toDateString())->whereKeyNot($news->id)
                        ->orderBy('sort_order')->orderByDesc('id')->limit(5)->get()
                    : collect(),
                'dayPublication' => $news->published_date
                    ? Publication::published()->where('publication_date', $news->published_date->toDateString())->first()
                    : null,
            ]);
        });
    }
}
