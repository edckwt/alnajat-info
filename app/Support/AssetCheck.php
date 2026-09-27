<?php

namespace App\Support;

use App\Models\PdfTemplate;
use App\Services\PdfBuilder;
use Throwable;

/**
 * فحص ملفات الموقع التي لا تُرفع مع الكود: css وjs وimages (من الموقع القديم، ومنها صور قوالب
 * النشرة images/vN وملفاتها css/pdf-vN.css)، وخطوط الـ PDF، ومجلد الصور المرفوعة ورابط public/storage.
 * محلياً تكون روابط رمزية إلى مجلد الموقع القديم، فإذا نُقل المشروع إلى الخادم كما هو صارت
 * روابط مكسورة واختفت الصور. الحل على الخادم: php artisan alnajat:assets --copy
 */
final class AssetCheck
{
    public const FIX = 'php artisan alnajat:assets --copy';

    /** @return list<string> وصف كل مشكلة بالعربية (فارغة = كل شيء موجود) */
    public static function problems(): array
    {
        $problems = [];

        foreach (['css', 'js', 'images'] as $dir) {
            if ($problem = self::dirProblem(public_path($dir), 'public/'.$dir)) {
                $problems[] = $problem;
            }
        }

        if (is_dir(public_path('css')) && is_dir(public_path('images'))) {
            $missing = [];
            foreach (range(1, (int) config('alnajat.pdf_latest_version', 5)) as $v) {
                if (! is_file(public_path("css/pdf-v{$v}.css")) || ! is_dir(public_path("images/v{$v}"))) {
                    $missing[] = 'v'.$v;
                }
            }
            if ($missing) {
                $problems[] = 'ملفات القوالب القديمة ناقصة ('.implode('، ', $missing).'): css/pdf-vN.css أو images/vN';
            }
        }

        // خطوط الـ PDF (resources/fonts من includes/custom-fonts في الموقع القديم)
        $fonts = (string) config('alnajat.pdf.fonts_path', resource_path('fonts'));
        if ($problem = self::dirProblem($fonts, self::relative($fonts))) {
            $problems[] = $problem.' (خطوط الـ PDF)';
        } else {
            $missingFonts = collect((array) config('alnajat.pdf.fonts', []))->flatten()
                ->reject(fn ($file) => is_file($fonts.'/'.$file))->values()->all();
            if ($missingFonts) {
                $problems[] = 'خطوط الـ PDF ناقصة: '.implode('، ', $missingFonts);
            }
        }

        $root = Media::root();
        if ($problem = self::dirProblem($root, self::relative($root))) {
            $problems[] = $problem.' (الصور المرفوعة)';
        }

        $storage = rtrim(storage_path('app/public'), '/').'/';
        if (str_starts_with($root.'/', $storage) && ! file_exists(public_path('storage'))) {
            $problems[] = is_link(public_path('storage'))
                ? 'رابط مكسور: public/storage ← '.readlink(public_path('storage')).' (أعد إنشاءه: php artisan storage:link)'
                : 'الرابط public/storage غير موجود: php artisan storage:link';
        }

        return $problems;
    }

    /**
     * صور القوالب المصمَّمة التي لا ملف لها على القرص.
     *
     * @param  iterable<PdfTemplate>|null  $templates
     * @return array<string, list<string>> المسار => أسماء القوالب
     */
    public static function missingTemplateImages(?iterable $templates = null): array
    {
        $missing = [];

        try {
            foreach ($templates ?? PdfTemplate::all(['id', 'name', 'design']) as $template) {
                foreach ($template->toDesign()->imagePaths() as $path) {
                    if (! preg_match('#^(https?:)?//#i', $path) && PdfBuilder::src($path) === null) {
                        $missing[$path][] = $template->name;
                    }
                }
            }
        } catch (Throwable) {
            return [];
        }

        return array_map(fn ($names) => array_values(array_unique($names)), $missing);
    }

    private static function dirProblem(string $path, string $label): ?string
    {
        if (is_link($path) && ! file_exists($path)) {
            return "رابط رمزي مكسور: {$label} ← ".readlink($path);
        }

        return is_dir($path) ? null : "المجلد غير موجود: {$label}";
    }

    private static function relative(string $path): string
    {
        return ltrim(str_starts_with($path, base_path()) ? substr($path, strlen(base_path())) : $path, '/');
    }
}
