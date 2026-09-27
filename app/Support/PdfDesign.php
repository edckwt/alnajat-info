<?php

namespace App\Support;

use App\Services\PdfBuilder;

/**
 * تصميم قالب نشرة (JSON محفوظ في pdf_templates.design) وتحويله إلى HTML يفهمه mPDF.
 *
 * كل الأبعاد بالمليمتر، والخطوط بالنقطة (pt)، حتى يطابق المحرر ما يطبعه mPDF.
 * ثلاث صفحات: cover (الغلاف)، section (تتكرر لكل خبر/قسم، وفيها منطقة المحتوى)، last (الختام).
 * العناصر تُرسم بمواضع ثابتة على الصفحة (position:fixed لكتل مباشرة تحت body، كما يدعمها mPDF).
 */
final class PdfDesign
{
    public const PAGES = ['cover' => 'الغلاف', 'section' => 'الصفحة المتكررة', 'last' => 'الختام'];

    public const TYPES = ['text', 'image', 'rect', 'line', 'qr'];

    /** مقاسات الورق بالمليمتر (طولي). */
    public const FORMATS = ['A4' => [210, 297], 'A5' => [148, 210], 'Letter' => [215.9, 279.4], 'Legal' => [215.9, 355.6]];

    /** المتغيرات المتاحة في النصوص: {{اسم}} */
    public const VARIABLES = [
        'date_long' => 'التاريخ كاملاً (الجمعة 25 سبتمبر 2026)',
        'date_hijri' => 'التاريخ الهجري',
        'date' => 'التاريخ (2026-09-25)',
        'day_name' => 'اسم اليوم',
        'publication_title' => 'عنوان النشرة',
        'publication_number' => 'رقم النشرة',
        'site_title' => 'اسم الموقع',
        'site_slogan' => 'وصف الموقع المختصر',
        'category_name' => 'اسم القسم (الصفحة المتكررة)',
        'page' => 'رقم الصفحة',
        'pages' => 'عدد الصفحات',
        'site_url' => 'رابط الموقع',
        'publication_url' => 'رابط النشرة PDF',
        'archive_url' => 'رابط أرشيف النشرات',
        'other_file_url' => 'رابط ملحق النشرة',
        'whatsapp' => 'رقم الواتساب',
    ];

    /** @param  array<string,mixed>  $design */
    private function __construct(private array $design) {}

    /** يقبل أي JSON ويعيد تصميماً صالحاً (قيم ناقصة → افتراضية، عناصر غير معروفة → تُحذف). */
    public static function fromArray(array $data): self
    {
        $page = (array) ($data['page'] ?? []);
        $format = array_key_exists($page['format'] ?? '', self::FORMATS) ? $page['format'] : 'A4';
        $orientation = ($page['orientation'] ?? 'P') === 'L' ? 'L' : 'P';

        $design = [
            'v' => 1,
            'page' => [
                'format' => $format,
                'orientation' => $orientation,
                'font' => is_string($page['font'] ?? null) ? $page['font'] : 'tajawal',
            ],
            'pages' => [],
        ];

        [$w, $h] = self::size($format, $orientation);

        foreach (array_keys(self::PAGES) as $name) {
            $source = (array) ($data['pages'][$name] ?? []);
            $out = [
                'background' => self::background($source['background'] ?? []),
                'elements' => array_values(array_filter(array_map(
                    fn ($el) => self::element((array) $el, $w, $h),
                    (array) ($source['elements'] ?? []),
                ))),
            ];

            if ($name === 'section') {
                $content = (array) ($source['content'] ?? []);
                $out['content'] = [
                    'x' => self::num($content['x'] ?? 12, 0, $w - 20),
                    'y' => self::num($content['y'] ?? 40, 0, $h - 20),
                    'w' => self::num($content['w'] ?? $w - 24, 20, $w),
                    'h' => self::num($content['h'] ?? $h - 60, 20, $h),
                ];
                $out['categoryBackgrounds'] = collect((array) ($source['categoryBackgrounds'] ?? []))
                    ->filter(fn ($path, $id) => ctype_digit((string) $id) && self::safePath($path))
                    ->map(fn ($path) => (string) $path)
                    ->all();
            }

            $design['pages'][$name] = $out;
        }

        return new self($design);
    }

    public function toArray(): array
    {
        return $this->design;
    }

    /** @return array{0: float, 1: float} العرض والارتفاع بالمليمتر */
    public static function size(string $format, string $orientation): array
    {
        [$w, $h] = self::FORMATS[$format] ?? self::FORMATS['A4'];

        return $orientation === 'L' ? [$h, $w] : [$w, $h];
    }

    public function pageSize(): array
    {
        return self::size($this->design['page']['format'], $this->design['page']['orientation']);
    }

    public function format(): string
    {
        return $this->design['page']['format'];
    }

    public function orientation(): string
    {
        return $this->design['page']['orientation'];
    }

    public function font(): string
    {
        return $this->design['page']['font'];
    }

    public function page(string $name): array
    {
        return $this->design['pages'][$name];
    }

    // ------------------------------------------------------------------ mPDF

    /**
     * قواعد @page: صفحة مسماة لكل من الغلاف والختام، وللصفحة المتكررة (ولكل قسم له خلفية خاصة).
     * هوامش الصفحة المتكررة = حدود منطقة المحتوى، فيتدفق فيها المحتوى وحدها.
     */
    public function pageCss(): string
    {
        [$w, $h] = $this->pageSize();
        $css = '';

        foreach (['cover', 'last'] as $name) {
            $css .= '@page '.$name.' { margin: 0; '.$this->backgroundCss($this->page($name)['background']).' }'."\n";
        }

        $section = $this->page('section');
        $c = $section['content'];
        $margins = sprintf('margin-top: %smm; margin-right: %smm; margin-bottom: %smm; margin-left: %smm; margin-header: 0; margin-footer: 0;',
            $c['y'], max(0, $w - $c['x'] - $c['w']), max(0, $h - $c['y'] - $c['h']), $c['x']);

        $css .= '@page section { '.$margins.' '.$this->backgroundCss($section['background']).' }'."\n";

        foreach ($section['categoryBackgrounds'] as $categoryId => $image) {
            $css .= '@page section_c'.$categoryId.' { '.$margins.' '.$this->backgroundCss(['image' => $image] + $section['background']).' }'."\n";
        }

        return $css;
    }

    public function sectionSelector(?int $categoryId): string
    {
        return $categoryId && isset($this->page('section')['categoryBackgrounds'][$categoryId]) ? 'section_c'.$categoryId : 'section';
    }

    /** HTML العناصر الثابتة لصفحة، بعد استبدال المتغيرات. */
    public function elementsHtml(string $page, array $vars): string
    {
        return implode("\n", array_map(fn (array $el) => $this->elementHtml($el, $vars), $this->page($page)['elements']));
    }

    private function elementHtml(array $el, array $vars): string
    {
        $s = $el['style'];
        $box = sprintf('position: fixed; left: %smm; top: %smm; width: %smm; height: %smm;', $el['x'], $el['y'], $el['w'], $el['h']);
        // الرابط داخل الكتلة (mPDF يشترط أن تكون الكتلة الثابتة ابناً مباشراً لـ body)
        $link = trim(self::fill($el['link'] ?? '', $vars));

        return match ($el['type']) {
            'text' => $this->textHtml($el, $vars, $box, $link),
            'image' => $this->imageHtml($el, $box, $link),
            'rect' => '<div style="'.$box.' background-color: '.($s['bg'] ?? 'transparent').'; border-radius: '.$s['radius'].'mm;'
                .($s['borderWidth'] > 0 ? ' border: '.$s['borderWidth'].'mm solid '.$s['borderColor'].';' : '').'"></div>',
            'line' => '<div style="position: fixed; left: '.$el['x'].'mm; top: '.$el['y'].'mm; width: '.$el['w'].'mm; height: '.max(0.2, $s['borderWidth']).'mm; background-color: '.$s['color'].';"></div>',
            'qr' => $this->qrHtml($el, $vars, $box),
            default => '',
        };
    }

    /** رمز QR عبر mpdf/qrcode؛ بدونها يُطبع الرابط نصاً حتى لا تفشل النشرة. */
    private function qrHtml(array $el, array $vars, string $box): string
    {
        $data = self::fill($el['qr'], $vars);

        if (! class_exists(\Mpdf\QrCode\QrCode::class)) {
            return '<div style="'.$box.' font-size: 7pt; direction: ltr; text-align: center; overflow: hidden;">'.e($data).'</div>';
        }

        return '<div style="'.$box.'"><barcode code="'.e($data).'" type="QR" size="'.round($el['w'] / 25, 2).'" error="M" disableborder="1" /></div>';
    }

    private function textHtml(array $el, array $vars, string $box, string $link = ''): string
    {
        $s = $el['style'];
        $style = $box
            .' font-family: '.($s['font'] === 'inherit' ? $this->font() : $s['font']).'; font-size: '.$s['size'].'pt; line-height: '.$s['lineHeight'].';'
            .' color: '.$s['color'].'; text-align: '.$s['align'].'; font-weight: '.($s['bold'] ? 'bold' : 'normal').'; direction: rtl;'
            .($s['bg'] ? ' background-color: '.$s['bg'].';' : '')
            .($s['radius'] > 0 ? ' border-radius: '.$s['radius'].'mm;' : '')
            .($s['padding'] > 0 ? ' padding: '.$s['padding'].'mm;' : '')
            // shrink: mPDF يصغّر النص ليناسب الإطار
            .' overflow: '.($s['shrink'] ? 'auto' : 'visible').';';

        $text = nl2br(e(self::fill($el['text'], $vars)));
        if ($link !== '') {
            $text = '<a href="'.e($link).'" style="color: '.$s['color'].'; text-decoration: none;">'.$text.'</a>';
        }

        return '<div style="'.$style.'">'.$text.'</div>';
    }

    private function imageHtml(array $el, string $box, string $link = ''): string
    {
        $src = PdfBuilder::src($el['src']);
        if (! $src) {
            return '';
        }

        $size = $el['fit'] === 'width' ? 'width: '.$el['w'].'mm;' : 'width: '.$el['w'].'mm; height: '.$el['h'].'mm;';

        $img = '<img src="'.e($src).'" style="'.$size.'">';

        return '<div style="'.$box.' overflow: hidden;">'.($link !== '' ? '<a href="'.e($link).'">'.$img.'</a>' : $img).'</div>';
    }

    private function backgroundCss(array $background): string
    {
        $css = 'background-color: '.$background['color'].';';
        if ($background['image'] && ($src = PdfBuilder::src($background['image']))) {
            $css .= ' background: '.$background['color'].' url("'.$src.'") no-repeat 0 0; background-image-resize: '.(($background['fit'] ?? 'fill') === 'width' ? 4 : 6).';';
        }

        return $css;
    }

    /** يستبدل {{متغير}} بقيمته؛ page/pages تبقى رموز mPDF ({PAGENO}، {nbpg}). */
    public static function fill(string $text, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/', function ($m) use ($vars) {
            return match ($m[1]) {
                'page' => '{PAGENO}',
                'pages' => '{nbpg}',
                default => (string) ($vars[$m[1]] ?? ''),
            };
        }, $text) ?? $text;
    }

    // ------------------------------------------------------------------ تنقية

    private static function background(mixed $bg): array
    {
        $bg = (array) $bg;

        return [
            'image' => self::safePath($bg['image'] ?? null) ? (string) $bg['image'] : null,
            'color' => self::color($bg['color'] ?? null) ?? '#ffffff',
            // fill = بحجم الصفحة كاملة، width = بعرض الصفحة من أعلاها بنسبة الصورة (مثل ترويسات القوالب القديمة)
            'fit' => ($bg['fit'] ?? 'fill') === 'width' ? 'width' : 'fill',
        ];
    }

    private static function element(array $el, float $pw, float $ph): ?array
    {
        $type = $el['type'] ?? null;
        if (! in_array($type, self::TYPES, true)) {
            return null;
        }

        $style = (array) ($el['style'] ?? []);

        $out = [
            'id' => preg_match('/^[a-z0-9_-]{1,40}$/i', (string) ($el['id'] ?? '')) ? (string) $el['id'] : 'e'.substr(md5(json_encode($el)), 0, 8),
            'type' => $type,
            'name' => mb_substr(trim((string) ($el['name'] ?? '')), 0, 60),
            'x' => self::num($el['x'] ?? 10, -$pw, $pw * 2),
            'y' => self::num($el['y'] ?? 10, -$ph, $ph * 2),
            'w' => self::num($el['w'] ?? 50, 1, $pw * 2),
            'h' => self::num($el['h'] ?? 10, 0.2, $ph * 2),
            'link' => mb_substr((string) ($el['link'] ?? ''), 0, 500),
            'style' => [
                'font' => preg_match('/^[a-z0-9-]{1,60}$/', (string) ($style['font'] ?? '')) ? (string) $style['font'] : 'inherit',
                'size' => self::num($style['size'] ?? 14, 4, 200),
                'lineHeight' => self::num($style['lineHeight'] ?? 1.4, 0.8, 4),
                'bold' => (bool) ($style['bold'] ?? false),
                'align' => in_array($style['align'] ?? null, ['right', 'center', 'left', 'justify'], true) ? $style['align'] : 'right',
                'color' => self::color($style['color'] ?? null) ?? '#13232e',
                'bg' => self::color($style['bg'] ?? null),
                'radius' => self::num($style['radius'] ?? 0, 0, 100),
                'padding' => self::num($style['padding'] ?? 0, 0, 50),
                'borderWidth' => self::num($style['borderWidth'] ?? 0, 0, 20),
                'borderColor' => self::color($style['borderColor'] ?? null) ?? '#13232e',
                'shrink' => (bool) ($style['shrink'] ?? false),
            ],
        ];

        if ($type === 'text') {
            $out['text'] = mb_substr((string) ($el['text'] ?? ''), 0, 5000);
        } elseif ($type === 'image') {
            $out['src'] = self::safePath($el['src'] ?? null) ? (string) $el['src'] : null;
            $out['fit'] = ($el['fit'] ?? 'stretch') === 'width' ? 'width' : 'stretch';
        } elseif ($type === 'qr') {
            $out['qr'] = mb_substr((string) ($el['qr'] ?? '{{publication_url}}'), 0, 500);
        }

        return $out;
    }

    private static function num(mixed $value, float $min, float $max): float
    {
        $n = is_numeric($value) ? (float) $value : $min;

        return round(max($min, min($max, $n)), 2);
    }

    private static function color(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value) ? strtolower($value) : null;
    }

    /** مسار داخل الموقع (upload/…، images/…) أو رابط http(s)؛ لا «..» ولا مخططات أخرى. */
    private static function safePath(mixed $path): bool
    {
        return is_string($path) && $path !== '' && strlen($path) < 500 && ! str_contains($path, '..')
            && (preg_match('#^(upload|images)/[A-Za-z0-9_./-]+$#', $path) || preg_match('#^https?://[^\s"\'<>()]+$#i', $path));
    }
}
