<?php

namespace App\Services;

use App\Models\Publication;
use Illuminate\Support\Facades\Cache;

/**
 * قوالب النشرة الحالية (النسخ 1..N): كل نسخة ملف css/pdf-vN.css وصورها في images/vN.
 * تُستخرج صور الغلاف وصفحة القسم والخاتمة من ملف الـ CSS نفسه، فتبقى المعاينة
 * مطابقة لما يولّده mPDF حتى لو غُيّرت الصور.
 */
class PdfTemplates
{
    private const NAMES = [1 => 'الأول', 2 => 'الثاني', 3 => 'الثالث', 4 => 'الرابع', 5 => 'الخامس', 6 => 'السادس', 7 => 'السابع', 8 => 'الثامن'];

    /** @param  string|null  $publicPath  مجلد public (يُغيَّر في الاختبارات) */
    public function __construct(private readonly ?string $publicPath = null) {}

    /**
     * @return array<int, array{version:int, name:string, cover:?string, section:?string, last:?string, count:int, from:?string, to:?string, latest:bool}>
     */
    public function all(): array
    {
        $latest = (int) config('alnajat.pdf_latest_version');

        $usage = Cache::remember('pdf.templates.usage', 600, fn () => Publication::query()
            ->selectRaw('pdf_version, count(*) as total, min(publication_date) as first_date, max(publication_date) as last_date')
            ->groupBy('pdf_version')
            ->get()
            ->keyBy('pdf_version')
            ->map(fn ($row) => ['count' => (int) $row->total, 'from' => $row->first_date, 'to' => $row->last_date])
            ->all());

        $templates = [];
        foreach (range(1, $latest) as $version) {
            $css = $this->path("css/pdf-v{$version}.css");
            $css = is_file($css) ? (string) file_get_contents($css) : '';
            $last = "images/v{$version}/pdf-footer-last-page.png";

            $templates[$version] = [
                'version' => $version,
                'name' => 'القالب '.(self::NAMES[$version] ?? $version),
                'cover' => $this->backgroundOf($css, '.first_page'),
                'section' => $this->backgroundOf($css, '.magazine_page'),
                'last' => is_file($this->path($last)) ? $last : null,
                'count' => $usage[$version]['count'] ?? 0,
                'from' => isset($usage[$version]['from']) ? substr((string) $usage[$version]['from'], 0, 7) : null,
                'to' => isset($usage[$version]['to']) ? substr((string) $usage[$version]['to'], 0, 7) : null,
                'latest' => $version === $latest,
            ];
        }

        return $templates;
    }

    /** مسار صورة الخلفية في قاعدة CSS معيّنة: ".first_page { background: … url({site_url}images/v5/first.jpg?v=4) … }" */
    public function backgroundOf(string $css, string $selector): ?string
    {
        // جسم القاعدة حتى أول «}» في بداية سطر ({site_url} نفسه يحتوي «}»)
        if (! preg_match('/(?:^|\})\s*'.preg_quote($selector, '/').'\s*\{(.*?)\n\s*\}/s', $css, $rule)) {
            return null;
        }

        if (! preg_match('/url\(\s*[\'"]?\{site_url\}([^)\'"?\s]+)/', $rule[1], $url)) {
            return null;
        }

        return is_file($this->path($url[1])) ? $url[1] : null;
    }

    private function path(string $relative): string
    {
        return $this->publicPath !== null ? rtrim($this->publicPath, '/').'/'.ltrim($relative, '/') : public_path($relative);
    }

    /** نص css/pdf-vN.css (فارغ إن لم يوجد). */
    public function cssOf(int $version): string
    {
        $file = $this->path("css/pdf-v{$version}.css");

        return is_file($file) ? (string) file_get_contents($file) : '';
    }

    /** مسار داخل public إن وُجد الملف. */
    public function existing(string $relative): ?string
    {
        return is_file($this->path($relative)) ? $relative : null;
    }
}
