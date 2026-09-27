<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\PdfDesign;

/**
 * تصاميم بداية لمحرر القوالب: قالب فارغ مرتب، أو نسخة من قالب قديم (css/pdf-vN.css)
 * بصوره ومواضع عناصره تقريباً، ليُعدَّل بالسحب والإفلات بدل البدء من الصفر.
 */
class PdfTemplateFactory
{
    /** بكسل التصميم القديم (عرض 795) إلى مليمتر A4. */
    private const PX = 210 / 795;

    public function __construct(private readonly PdfTemplates $templates) {}

    public function blank(): array
    {
        $font = (string) (Setting::get('pdf_font') ?: 'tajawal');

        return PdfDesign::fromArray([
            'page' => ['format' => 'A4', 'orientation' => 'P', 'font' => $font],
            'pages' => [
                'cover' => [
                    'background' => ['color' => '#0b4f6c'],
                    'elements' => [
                        $this->text('title', 20, 70, 170, 30, '{{site_title}}', 30, '#ffffff', true, 'center'),
                        $this->text('date', 20, 110, 170, 12, '{{date_long}}', 18, '#fbebd3', false, 'center'),
                        $this->text('hijri', 20, 124, 170, 10, '{{date_hijri}}', 13, '#cfe3ea', false, 'center'),
                        ['id' => 'archive', 'type' => 'text', 'name' => 'رابط الأرشيف', 'x' => 65, 'y' => 250, 'w' => 80, 'h' => 12,
                            'text' => 'النشرات السابقة', 'link' => '{{archive_url}}',
                            'style' => ['size' => 14, 'bold' => true, 'align' => 'center', 'color' => '#13232e', 'bg' => '#e09f3e', 'radius' => 6, 'padding' => 2]],
                    ],
                ],
                'section' => [
                    'background' => ['color' => '#ffffff'],
                    'content' => ['x' => 12, 'y' => 32, 'w' => 186, 'h' => 245],
                    'elements' => [
                        ['id' => 'band', 'type' => 'rect', 'name' => 'شريط العنوان', 'x' => 0, 'y' => 0, 'w' => 210, 'h' => 24, 'style' => ['bg' => '#0b4f6c']],
                        $this->text('category', 12, 7, 186, 12, '{{category_name}}', 18, '#ffffff', true, 'right'),
                        $this->text('page', 12, 282, 186, 8, 'صفحة {{page}}', 10, '#5b6770', false, 'center'),
                    ],
                ],
                'last' => [
                    'background' => ['color' => '#0b4f6c'],
                    'elements' => [
                        $this->text('thanks', 20, 120, 170, 20, '{{site_title}}', 24, '#ffffff', true, 'center'),
                        $this->text('slogan', 20, 142, 170, 12, '{{site_slogan}}', 14, '#cfe3ea', false, 'center'),
                        ['id' => 'qr', 'type' => 'qr', 'name' => 'رمز الموقع', 'x' => 85, 'y' => 175, 'w' => 40, 'h' => 40, 'qr' => '{{site_url}}'],
                    ],
                ],
            ],
        ])->toArray();
    }

    /** نسخة قابلة للتصميم من القالب القديم رقم $version. */
    public function fromLegacy(int $version): array
    {
        $css = $this->templates->cssOf($version);
        $px = fn (float $value) => round($value * self::PX, 1);

        $categoryBackgrounds = [];
        foreach (config('alnajat.pdf.page_classes') as $categoryId => $class) {
            if ($class !== 'magazine_page' && ($image = $this->templates->backgroundOf($css, '.'.$class))) {
                $categoryBackgrounds[$categoryId] = $image;
            }
        }

        $cover = [];
        // .first_page_date { padding-top: 300px } ثم هامش الفقرة: السطر يبدأ نحو 328px، بين العنوان والشعار في صورة الغلاف
        $cover[] = $this->text('date', 10, $px(328), 190, 12, '{{date_long}}', 19, '#f2808e', true, 'center');
        if ($archive = $this->templates->existing("images/v{$version}/archive-ar-2.png")) {
            $cover[] = ['id' => 'archive', 'type' => 'image', 'name' => 'زر الأرشيف', 'x' => 128, 'y' => 229, 'w' => 66, 'h' => 17,
                'src' => $archive, 'fit' => 'width', 'link' => '{{archive_url}}'];
        }

        $section = [$this->text('category', 132, $px(35), 77, 10, '{{category_name}}', 16.5, '#ffffff', false, 'center')];
        if ($footer = $this->templates->backgroundOf($css, '.pdf_footer')) {
            $section[] = ['id' => 'footer', 'type' => 'image', 'name' => 'شريط التذييل', 'x' => 0, 'y' => 276, 'w' => 210, 'h' => 21, 'src' => $footer, 'fit' => 'width'];
        }
        $section[] = $this->text('page', 168, 278, 15, 8, '{{page}}', 15, '#ffffff', true, 'center');

        return PdfDesign::fromArray([
            'page' => ['format' => 'A4', 'orientation' => 'P', 'font' => (string) (Setting::get('pdf_font') ?: 'tajawal')],
            'pages' => [
                'cover' => [
                    'background' => ['image' => $this->templates->backgroundOf($css, '.first_page'), 'color' => '#eeeeee', 'fit' => 'width'],
                    'elements' => $cover,
                ],
                'section' => [
                    'background' => ['image' => $this->templates->backgroundOf($css, '.magazine_page'), 'color' => '#ffffff', 'fit' => 'width'],
                    'content' => ['x' => $px(40), 'y' => $px(135), 'w' => 210 - 2 * $px(40), 'h' => 297 - $px(135) - 24],
                    'categoryBackgrounds' => $categoryBackgrounds,
                    'elements' => $section,
                ],
                'last' => [
                    'background' => ['image' => $this->templates->existing("images/v{$version}/pdf-footer-last-page.png"), 'color' => '#ffffff', 'fit' => 'width'],
                    'elements' => [],
                ],
            ],
        ])->toArray();
    }

    private function text(string $id, float $x, float $y, float $w, float $h, string $text, float $size, string $color, bool $bold, string $align): array
    {
        $names = ['title' => 'العنوان', 'date' => 'التاريخ', 'hijri' => 'التاريخ الهجري', 'category' => 'اسم القسم', 'page' => 'رقم الصفحة', 'thanks' => 'اسم الموقع', 'slogan' => 'الوصف'];

        return ['id' => $id, 'type' => 'text', 'name' => $names[$id] ?? $id, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'text' => $text,
            'style' => ['size' => $size, 'color' => $color, 'bold' => $bold, 'align' => $align]];
    }
}
