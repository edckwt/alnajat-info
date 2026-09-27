<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeBox;
use App\Models\News;
use App\Models\Publication;
use App\Models\PdfTemplate;
use App\Models\Setting;
use App\Support\ArabicDate;
use App\Support\Media;
use App\Support\PdfDesign;
use Illuminate\Support\Collection;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;

/**
 * يولّد النشرة اليومية وملف الخبر بصيغة PDF بنفس منطق includes/pdf.php القديم
 * (create_pdf + get_news_pdf + news_template_pdf + pdf_template)، ونفس ملفات
 * css/pdf-v1..v5.css والصور، حتى تطابق النشرات القديمة شكلها الأصلي.
 */
class PdfBuilder
{
    /**
     * @param  int|null  $version  قالب قديم غير قالب النشرة (للمعاينة قبل الحفظ)
     * @param  int|null  $sample  معاينة سريعة: أول N أقسام بخبر واحد لكل قسم
     * @param  PdfDesign|null  $design  قالب مصمَّم (للمعاينة قبل الحفظ)
     * @return string محتوى ملف الـ PDF
     */
    public function publication(Publication $publication, ?int $version = null, ?int $sample = null, ?PdfDesign $design = null): string
    {
        // النشرة المرتبطة بقالب مصمَّم تُرسم به، ما لم يُطلب قالب قديم بعينه.
        if ($design === null && $version === null && $publication->pdf_template_id) {
            $design = PdfTemplate::find($publication->pdf_template_id)?->toDesign();
        }

        if ($design !== null) {
            return $this->fromDesign($publication, $design, $sample);
        }

        $version = $version ?: ((int) $publication->pdf_version ?: (int) config('alnajat.pdf_latest_version'));
        $mpdf = $this->makeMpdf($version, withLegacyHeaderFooter: true);

        $pages = [view('pdf.first-page', ['publication' => $publication, 'version' => $version])->render()];

        foreach ($this->boxes($publication, $sample) as $box) {
            $pages[] = $box['category']
                ? $this->categoryPages($box['category'], $box['type'], $box['news'])
                : $box['html'];
        }

        $pages[] = view('pdf.last-page', ['version' => $version])->render();

        $this->write($mpdf, $pages);

        return $mpdf->Output('', 'S');
    }

    /**
     * محتوى النشرة حسب «أقسام ملف الـ PDF» في الإعدادات: لكل صندوق كود، أو بانر، أو أخبار قسم.
     *
     * @return list<array{category: ?Category, type: int, news: ?Collection, html: ?string}>
     */
    private function boxes(Publication $publication, ?int $sample): array
    {
        $date = $publication->publication_date?->toDateString();
        $categories = Category::where('is_active', true)->get()->keyBy('id');
        $banners = Banner::where('is_active', true)->get()->keyBy('id');

        $out = [];
        $sections = 0;
        foreach (HomeBox::for(HomeBox::PDF)->get() as $box) {
            if ($sample !== null && $sections >= $sample) {
                break;
            }

            if (filled($box->code)) {
                $out[] = ['category' => null, 'type' => 0, 'news' => null, 'html' => $box->code];

                continue;
            }

            if ($box->banner_id && $banners->has($box->banner_id)) {
                $out[] = ['category' => null, 'type' => 0, 'news' => null, 'html' => view('pdf.banner', ['banner' => $banners[$box->banner_id]])->render()];

                continue;
            }

            $category = $categories->get($box->category_id);
            if (! $category || ! $date) {
                continue;
            }

            $news = $this->newsFor($category->id, $date, $sample !== null ? 1 : (int) $box->items_limit);
            if ($news->isEmpty()) {
                continue;
            }

            $out[] = ['category' => $category, 'type' => (int) $box->type, 'news' => $news, 'html' => null];
            $sections++;
        }

        return $out;
    }

    /**
     * نشرة بقالب مصمَّم: الغلاف، ثم لكل خبر (أو تغريدتين) صفحة متكررة بعناصرها الثابتة
     * ومنطقة محتوى يتدفق فيها الخبر بقالب صندوقه، ثم الختام.
     */
    private function fromDesign(Publication $publication, PdfDesign $design, ?int $sample): string
    {
        $mpdf = $this->makeMpdf((int) config('alnajat.pdf_latest_version'), withLegacyHeaderFooter: false, design: $design);
        $mpdf->WriteHTML($design->pageCss(), HTMLParserMode::HEADER_CSS);
        $vars = $this->designVariables($publication);

        $mpdf->AddPageByArray(['pageselector' => 'cover']);
        $mpdf->WriteHTML($design->elementsHtml('cover', $vars));

        $radio = (int) config('alnajat.pdf.radio_category');
        foreach ($this->boxes($publication, $sample) as $box) {
            $category = $box['category'];
            $pages = [$box['html']];

            if ($category) {
                $type = $category->id === $radio && $box['news']->count() === 1 ? 1 : $box['type'];
                $sections = $this->sections($type, $box['news']);
                // القسم الصوتي بقالب 3: كل أخباره في صفحة واحدة، كما في القالب القديم
                $pages = $category->id === $radio && $type === 3 ? [implode('', $sections)] : $sections;
            }

            foreach ($pages as $html) {
                $mpdf->AddPageByArray(['pageselector' => $design->sectionSelector($category?->id)]);
                $mpdf->WriteHTML($design->elementsHtml('section', $vars + ['category_name' => $category?->name ?? '']));
                $mpdf->WriteHTML(self::images((string) $html));
            }
        }

        $mpdf->AddPageByArray(['pageselector' => 'last']);
        $mpdf->WriteHTML($design->elementsHtml('last', $vars));

        return $mpdf->Output('', 'S');
    }

    /** @return array<string,string> قيم المتغيرات {{…}} في عناصر القالب */
    public function designVariables(Publication $publication): array
    {
        $date = $publication->publication_date ?? now();

        return [
            'date_long' => ArabicDate::long($date),
            'date_hijri' => ArabicDate::hijri($date),
            'date' => $date->format('Y-m-d'),
            'day_name' => ArabicDate::dayName($date),
            'publication_title' => (string) $publication->title,
            'publication_number' => (string) ($publication->id ?? ''),
            'site_title' => (string) Setting::get('site_title'),
            'site_slogan' => (string) Setting::get('site_slogan'),
            'site_url' => url('/'),
            'publication_url' => $publication->id ? route('publication.pdf', $publication->id) : route('pdf.today'),
            'archive_url' => route('publications.index').'?open=pdf',
            'other_file_url' => $publication->other_file ? Media::url($publication->other_file) : '',
            'whatsapp' => (string) Setting::get('site_whatsapp'),
        ];
    }

    /** ملف PDF لخبر واحد (pdf-show/{id}). */
    public function news(News $news): string
    {
        $mpdf = $this->makeMpdf((int) config('alnajat.pdf_latest_version'), withLegacyHeaderFooter: false);
        $mpdf->WriteHTML(self::images(view('pdf.news', ['news' => $news])->render()));

        return $mpdf->Output('', 'S');
    }

    /**
     * معاينة صندوق نشرة من الإعدادات: صفحات قسم واحد بقالب معيّن، بآخر يوم فيه أخبار للقسم.
     */
    public function preview(Category $category, int $type, int $limit): string
    {
        $date = News::published()
            ->where('hide_in_pdf', false)
            ->whereHas('categories', fn ($q) => $q->whereKey($category->id))
            ->max('published_date');

        $news = $date ? $this->newsFor($category->id, substr((string) $date, 0, 10), $limit) : collect();

        $pages = $news->isEmpty()
            ? ['<p style="text-align:center; padding-top:90mm; font-size:14pt">لا توجد أخبار منشورة في قسم «'.e($category->name).'» بعد.</p>']
            : $this->categoryPages($category, $type, $news);

        $mpdf = $this->makeMpdf((int) config('alnajat.pdf_latest_version'), withLegacyHeaderFooter: false);
        $this->write($mpdf, $pages);

        return $mpdf->Output('', 'S');
    }

    // ------------------------------------------------------------------

    /** أخبار قسم بتاريخ النشرة: آخر N خبر (بالأحدث) ثم بترتيب «ترتيب أخبار اليوم». */
    private function newsFor(int $categoryId, string $date, int $limit): Collection
    {
        return News::published()
            ->where('hide_in_pdf', false)
            ->where('published_date', $date)
            ->whereHas('categories', fn ($q) => $q->whereKey($categoryId))
            ->with('newspaper:id,name,logo')
            ->orderByDesc('id')
            ->limit(max($limit, 1))
            ->get()
            ->sortBy('sort_order')
            ->values();
    }

    /**
     * صفحات قسم (pdf_template القديمة): كل خبر (أو كل تغريدتين) في صفحة بخلفية القسم،
     * والقسم الصوتي بقالب 3 في صفحة واحدة.
     *
     * @return list<string>
     */
    private function categoryPages(Category $category, int $type, Collection $news): array
    {
        $radio = (int) config('alnajat.pdf.radio_category');

        // سلوك قديم: خبر صوتي واحد يُعرض بقالب الخبر الكبير (1).
        if ($category->id === $radio && $news->count() === 1) {
            $type = 1;
        }

        $sections = $this->sections($type, $news);
        $pageClass = config('alnajat.pdf.page_classes.'.$category->id, '');

        if ($category->id === $radio && $type === 3) {
            return [view('pdf.page', ['pageClass' => 'radio_page', 'category' => $category, 'sections' => $sections, 'plainTitle' => true])->render()];
        }

        return array_map(
            fn (string $section) => view('pdf.page', ['pageClass' => $pageClass, 'category' => $category, 'sections' => [$section], 'plainTitle' => false])->render(),
            $sections,
        );
    }

    /** news_template_pdf القديمة: عنصر لكل خبر، والتغريدات (2) كل اثنتين معاً. */
    private function sections(int $type, Collection $news): array
    {
        if ($type === 2) {
            return $news->chunk(2)
                ->map(fn ($pair) => view('pdf.section', ['type' => 2, 'posts' => $pair])->render())
                ->values()->all();
        }

        return $news->map(fn ($post) => view('pdf.section', ['type' => $type, 'posts' => collect([$post])])->render())->all();
    }

    /** pdf_write_html القديمة: كل عنصر في صفحة، أو نص متصل إن كان pdf_pages = 1. */
    private function write(Mpdf $mpdf, array $pages): void
    {
        $flat = [];
        foreach ($pages as $page) {
            foreach ((array) $page as $item) {
                if (trim((string) $item) !== '') {
                    $flat[] = $item;
                }
            }
        }

        if ((string) Setting::get('pdf_pages') === '1') {
            $mpdf->WriteHTML(self::images(implode('', $flat)));

            return;
        }

        foreach ($flat as $index => $html) {
            $mpdf->AddPage();
            $mpdf->WriteHTML(self::images($html));

            // رقم الصفحة في كل الصفحات بعد الغلاف
            $mpdf->SetHTMLFooter($index === 0 ? '' : '<div class="pdf_footer"><div class="pdf_footer_pages">{PAGENO}</div></div>');
        }
    }

    private function makeMpdf(int $version, bool $withLegacyHeaderFooter, ?PdfDesign $design = null): Mpdf
    {
        $fontDirs = (new ConfigVariables)->getDefaults()['fontDir'];
        $fontData = (new FontVariables)->getDefaults()['fontdata'];
        $custom = collect(config('alnajat.pdf.fonts'))
            ->filter(fn ($files) => is_file(config('alnajat.pdf.fonts_path').'/'.$files['R']))
            ->map(fn ($files) => $files + ['useOTL' => 0xFF, 'useKashida' => 75])
            ->all();

        // القالب المصمَّم يحدد الورق والخط، وهوامشه في صفحاته المسماة (@page).
        $font = $design?->font() ?? (string) Setting::get('pdf_font');
        $margin = fn (string $key) => $design ? 0.0 : (float) (Setting::get('pdf_margin_'.$key) ?: 0);

        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'autoArabic' => true,
            'tempDir' => $tempDir,
            'fontDir' => array_merge($fontDirs, [config('alnajat.pdf.fonts_path')]),
            'fontdata' => $fontData + $custom,
            'default_font' => isset($custom[$font]) ? $font : (array_key_first($custom) ?? 'dejavusans'),
            'format' => $design?->format() ?? (Setting::get('pdf_format') ?: 'A4'),
            'orientation' => $design?->orientation() ?? (Setting::get('pdf_orientation') ?: 'P'),
            'margin_left' => $margin('left'),
            'margin_right' => $margin('right'),
            'margin_top' => $margin('top'),
            'margin_bottom' => $margin('bottom'),
            'margin_header' => $margin('header'),
            'margin_footer' => $margin('footer'),
            // صور المواقع الأخرى فقط (صور الموقع نفسه تُقرأ من القرص): مهلة قصيرة حتى لا يطول التوليد
            'curlTimeout' => 3,
            'curlExecutionTimeout' => 6,
            'curlFollowLocation' => true,
        ]);

        $title = (string) Setting::get('site_title');
        $mpdf->SetTitle($title);
        $mpdf->SetAuthor('Al Najat charity');
        $mpdf->SetCreator('Al Najat charity');
        $mpdf->SetSubject($title.' | '.Setting::get('site_slogan'));
        $mpdf->SetKeywords('Charity, News');
        $mpdf->SetDirectionality('rtl');

        $mpdf->WriteHTML($this->css($version), HTMLParserMode::HEADER_CSS);

        // الهيدر والفوتر الثابتان كانا يعملان مع القالب 1 فقط.
        if ($withLegacyHeaderFooter && $version === 1) {
            if ((string) Setting::get('pdf_set_header') === '1') {
                $mpdf->SetHTMLHeader('<div style="text-align: right; font-weight: bold;">'.e($title).'</div>');
            }
            if ((string) Setting::get('pdf_set_footer') === '1') {
                $mpdf->SetHTMLFooter('<table width="100%"><tr><td width="33%">'.e($title).'</td><td width="33%">{PAGENO}/{nbpg}</td><td width="33%">{DATE j-m-Y}</td></tr></table>');
            }
        }

        return $mpdf;
    }

    /**
     * css/pdf-vN.css بعد استبدال {site_url} بمسار public/ على القرص
     * (أسرع بكثير من أن يطلب mPDF الصور من الموقع نفسه عبر HTTP).
     */
    public function css(int $version): string
    {
        $file = public_path("css/pdf-v{$version}.css");
        if (! is_file($file)) {
            $file = public_path('css/pdf-v'.config('alnajat.pdf_latest_version').'.css');
        }
        if (! is_file($file)) {
            return '';
        }

        return $this->localizeUrls(file_get_contents($file));
    }

    /** url({site_url}images/x.png) → مسار الملف في public/، وإن لم يوجد تُلغى الخلفية (none) بدل طلبها من الموقع. */
    public function localizeUrls(string $css): string
    {
        return (string) preg_replace_callback('/url\(\s*([\'"]?)\{site_url\}([^)\'"\s?]+)(\?[^)\'"\s]*)?\1\s*\)/', function ($m) {
            $local = public_path($m[2]);

            return is_file($local) ? 'url("'.$local.'")' : 'none';
        }, $css);
    }

    /**
     * مسار الصورة للـ PDF:
     *  - صور الموقع نفسه (نسبية أو بروابط نطاقاته): الملف من public/ على القرص، وإن لم يوجد تُتخطى (null).
     *    لا تُطلب أبداً من الموقع نفسه عبر HTTP: مع «php artisan serve» (عملية واحدة) أو قلة عمّال PHP
     *    ينتظر mPDF ردّاً من الخادم المشغول به، فيتوقف توليد النشرة والموقع معاً.
     *  - صور المواقع الأخرى: الرابط كما هو (بمهلة قصيرة في makeMpdf)، أو تُتخطى إن أُوقف
     *    alnajat.pdf.remote_images.
     */
    public static function src(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'data:image/')) {
            return $path;
        }

        // مسار محلي كامل جاهز (من استدعاء سابق)
        if (preg_match('#^(/|[A-Za-z]:[\\/])#', $path) && ! str_starts_with($path, '//') && is_file($path)) {
            return $path;
        }

        $isUrl = preg_match('#^(https?:)?//#i', $path) === 1;
        if ($isUrl && ! self::isOwnHost((string) parse_url(str_starts_with($path, '//') ? 'http:'.$path : $path, PHP_URL_HOST))) {
            return config('alnajat.pdf.remote_images', true) ? $path : null;
        }

        $relative = $isUrl ? (string) parse_url(str_starts_with($path, '//') ? 'http:'.$path : $path, PHP_URL_PATH) : $path;
        $relative = ltrim(rawurldecode((string) strtok($relative, '?#')), '/');

        foreach (array_unique([$relative, preg_replace('#^.*?/?((?:upload|images|css)/.+)$#', '$1', $relative)]) as $candidate) {
            if ($candidate !== '' && is_file($local = public_path($candidate))) {
                return $local;
            }
        }

        return null;
    }

    /**
     * صور داخل HTML (نص الخبر، البانر، صفحات القوالب): كل src يمر على src()،
     * والصورة التي لا ملف لها تُحذف بدل أن يحاول mPDF تحميلها.
     */
    public static function images(?string $html): string
    {
        $html = (string) $html;
        if ($html === '' || stripos($html, '<img') === false) {
            return $html;
        }

        return (string) preg_replace_callback('/<img\b[^>]*>/i', function (array $tag) {
            if (! preg_match('/\ssrc\s*=\s*(["\'])(.*?)\1/is', $tag[0], $attr)) {
                return '';
            }

            $src = self::src(html_entity_decode($attr[2], ENT_QUOTES | ENT_HTML5));

            return $src === null ? '' : str_replace($attr[0], ' src="'.e($src).'"', $tag[0]);
        }, $html);
    }

    /** نطاقات الموقع: الحالي، ورابط الموقع في الإعدادات، ونطاقات النظام القديم. */
    private static function isOwnHost(string $host): bool
    {
        static $hosts = null;
        $hosts ??= collect([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            parse_url(url('/'), PHP_URL_HOST),
            parse_url((string) Setting::get('site_url'), PHP_URL_HOST),
            ...(array) config('alnajat.pdf.own_hosts', ['alnajat.info', 'localhost', '127.0.0.1']),
        ])->filter()->map(fn ($h) => preg_replace('/^www\./', '', strtolower($h)))->unique()->all();

        return $host !== '' && in_array(preg_replace('/^www\./', '', strtolower($host)), $hosts, true);
    }
}
