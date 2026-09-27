<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeBox;
use App\Models\News;
use App\Models\Setting;
use App\Services\ImageUploader;
use App\Services\PdfBuilder;
use App\Support\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** الإعدادات العامة، إعدادات الـ PDF، وصناديق الصفحة الرئيسية والنشرة؛ كل تبويب بصلاحيته. */
class SettingController extends Controller
{
    /** المفاتيح النصية المسموح حفظها (نفس مفاتيح النظام القديم). */
    public const KEYS = [
        'site_title', 'site_slogan', 'site_description', 'site_logo', 'site_url',
        'subscribe_email', 'subscribe_whasapp', 'unsubscribe', 'site_whatsapp',
        'header_code', 'footer_code', 'newspaper_name', 'close_site', 'close_site_cause', 'site_theme',
        'pdf_set_header', 'pdf_set_footer', 'pdf_format', 'pdf_orientation', 'pdf_pages', 'pdf_font',
        'pdf_margin_left', 'pdf_margin_right', 'pdf_margin_top', 'pdf_margin_bottom', 'pdf_margin_header', 'pdf_margin_footer',
    ];

    public const PDF_FORMATS = ['A4', 'Letter', 'Legal', 'Executive', 'Folio', 'Demy', 'Royal', 'A', 'B'];

    public const PDF_FONTS = [
        'tajawal' => 'Tajawal', 'frutiger' => 'Frutiger', 'awanzamanth' => 'Awanzamanth', 'stc' => 'STC',
        'swissracondensed' => 'Swissracondensed', 'al-jazeera-arabic-regular' => 'Aljazeera',
        'bahij-insan' => 'Bahij', 'ae-almateen-bold' => 'Almateen', 'harf-fannan' => 'Fannan',
        'cairo' => 'Cairo', 'dubai' => 'Dubai',
    ];

    /** نوع الصندوق في الواجهة => الحقول التي يحتفظ بها عند الحفظ. */
    private const BOX_KINDS = ['news', 'banner', 'code', 'empty'];

    /** تبويبات الإعدادات؛ لكل تبويب صلاحية settings.<المفتاح>. */
    public const TABS = ['general' => 'إعدادات عامة', 'home' => 'الصفحة الرئيسية', 'pdf' => 'إعدادات ملف الـ PDF', 'pdf_boxes' => 'أقسام ملف الـ PDF'];

    /** @return array<string,string> التبويبات المسموحة للعضو */
    private function allowedTabs(Request $request): array
    {
        return array_filter(self::TABS, fn ($label, $key) => $request->user()->can("settings.$key"), ARRAY_FILTER_USE_BOTH);
    }

    public function edit(Request $request): View
    {
        $tabs = $this->allowedTabs($request);
        abort_if($tabs === [], 403);

        $boxes = HomeBox::orderBy('position')->get()->groupBy('context');

        return view('admin.settings.edit', [
            'tabs' => $tabs,
            'settings' => Setting::values(),
            'themes' => Theme::THEMES,
            'activeTheme' => Theme::active(),
            'tab' => array_key_exists((string) $request->query('tab'), $tabs) ? $request->query('tab') : array_key_first($tabs),
            'homeBoxes' => $this->fillBoxes($boxes->get(HomeBox::HOME, collect())),
            'pdfBoxes' => $this->fillBoxes($boxes->get(HomeBox::PDF, collect())),
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'banners' => Banner::orderByDesc('id')->pluck('title', 'id'),
            'pdfFormats' => self::PDF_FORMATS,
            // الخطوط الموجودة ملفاتها فعلاً في resources/fonts
            'pdfFonts' => collect(config('alnajat.pdf.fonts'))
                ->filter(fn ($files) => is_file(config('alnajat.pdf.fonts_path').'/'.$files['R']))
                ->mapWithKeys(fn ($files, $font) => [$font => self::PDF_FONTS[$font] ?? Str::headline($font)])
                ->all(),
        ]);
    }

    public function update(Request $request, ImageUploader $uploader): RedirectResponse
    {
        $request->validate([
            'setting' => ['array'],
            'setting.*' => ['nullable', 'string', 'max:65000'],
            'setting.site_theme' => ['nullable', Rule::in(array_keys(Theme::THEMES))],
            'logo_file' => ['nullable', 'image', 'max:'.config('alnajat.image_max_kb')],
            'boxes' => ['array'],
            'boxes.*.*.kind' => ['nullable', Rule::in(self::BOX_KINDS)],
            'boxes.*.*.position' => ['nullable', 'integer', 'min:1', 'max:'.config('alnajat.home_boxes', 15)],
            'boxes.*.*.category_id' => ['nullable', 'integer', 'exists:categories,id', 'required_if:boxes.*.*.kind,news'],
            'boxes.*.*.banner_id' => ['nullable', 'integer', 'exists:banners,id', 'required_if:boxes.*.*.kind,banner'],
            'boxes.*.*.items_limit' => ['nullable', 'integer', 'min:0', 'max:500'],
            'boxes.*.*.type' => ['nullable', 'integer', 'min:0', 'max:10'],
            'boxes.*.*.code' => ['nullable', 'string', 'max:65000', 'required_if:boxes.*.*.kind,code'],
        ], [
            'boxes.*.*.category_id.required_if' => 'اختر القسم.',
            'boxes.*.*.banner_id.required_if' => 'اختر البانر.',
            'boxes.*.*.code.required_if' => 'اكتب الكود.',
        ], ['logo_file' => 'الشعار']);

        $tabs = $this->allowedTabs($request);
        abort_if($tabs === [], 403);

        // كل مجموعة مفاتيح تُحفظ فقط لمن يملك صلاحية تبويبها.
        $keys = array_values(array_filter(self::KEYS, fn ($key) => isset($tabs[str_starts_with($key, 'pdf_') ? 'pdf' : 'general'])));
        $contexts = array_keys(array_filter([HomeBox::HOME => isset($tabs['home']), HomeBox::PDF => isset($tabs['pdf_boxes'])]));

        DB::transaction(function () use ($request, $uploader, $keys, $tabs, $contexts) {
            $values = collect($request->input('setting', []))->only($keys)->map(fn ($v) => $v ?? '')->all();

            if ($request->hasFile('logo_file') && isset($tabs['general'])) {
                $values['site_logo'] = $uploader->store($request->file('logo_file'), 'logo');
            }

            if ($values !== []) {
                Setting::put($values);
            }

            foreach ($request->input('boxes', []) as $context => $rows) {
                if (! in_array($context, $contexts, true)) {
                    continue;
                }

                foreach ($rows as $position => $row) {
                    // الترتيب بالسحب يرسل الموضع الجديد في position
                    HomeBox::updateOrCreate(
                        ['context' => $context, 'position' => (int) ($row['position'] ?? $position)],
                        $this->boxValues($row),
                    );
                }
            }
        });

        return redirect()
            ->route('admin.settings.edit', ['tab' => $request->input('tab', 'general')])
            ->with('status', 'تم حفظ الإعدادات.');
    }

    /**
     * معاينة صندوق بقالبه وآخر أخبار قسمه: صفحة HTML بتنسيق الموقع، أو PDF بتنسيق النشرة.
     * تُفتح داخل نافذة في صفحة الإعدادات قبل الحفظ.
     */
    public function preview(Request $request, PdfBuilder $pdf): Response|View
    {
        $validator = Validator::make($request->query(), [
            'context' => ['required', Rule::in([HomeBox::HOME, HomeBox::PDF])],
            'category_id' => ['required', 'integer'],
            'type' => ['required', 'integer', 'between:1,10'],
            'limit' => ['nullable', 'integer', 'min:1'],
        ]);
        abort_if($validator->fails(), 404);

        $data = $validator->validated();
        abort_unless($request->user()->can($data['context'] === HomeBox::PDF ? 'settings.pdf_boxes' : 'settings.home'), 403);
        $category = Category::findOrFail($data['category_id']);
        $type = (int) $data['type'];

        if ($data['context'] === HomeBox::PDF) {
            return response($pdf->preview($category, $type, min((int) ($data['limit'] ?? 5), 10)), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="preview.pdf"',
                'Cache-Control' => 'no-store',
            ]);
        }

        // المعاينة بالواجهة المعتمدة للزوار (الجديدة لها ملف معاينة بتنسيقها في مجلدها)
        Theme::apply(Theme::active());

        return view('admin.settings.preview', [
            'category' => $category,
            'type' => $type,
            'template' => config("alnajat.box_templates.home.$type"),
            'posts' => News::published()
                ->with('newspaper:id,name,logo')
                ->whereHas('categories', fn ($q) => $q->whereKey($category->id))
                ->orderByDesc('id')
                ->limit(min((int) ($data['limit'] ?? 5), 20))
                ->get(),
        ]);
    }

    /** ملف خط النشرة لعرض عيّنته في صفحة الإعدادات. */
    public function font(string $font): BinaryFileResponse
    {
        $file = config("alnajat.pdf.fonts.$font.R");
        $path = $file ? config('alnajat.pdf.fonts_path').'/'.$file : null;

        abort_unless($path && is_file($path), 404);

        return response()->file($path, ['Content-Type' => 'font/ttf', 'Cache-Control' => 'private, max-age=604800']);
    }

    /**
     * قيم الصندوق حسب نوعه: الحقول الخاصة بالأنواع الأخرى تُفرَّغ حتى لا يظهر
     * بانر مخفي بدل الأخبار مثلاً. بدون kind (طلب قديم) تُحفظ القيم كما هي.
     *
     * @param  array<string,mixed>  $row
     * @return array<string,mixed>
     */
    private function boxValues(array $row): array
    {
        $values = [
            'category_id' => ($row['category_id'] ?? null) ?: null,
            'banner_id' => ($row['banner_id'] ?? null) ?: null,
            'items_limit' => (int) ($row['items_limit'] ?? 0),
            'type' => (int) ($row['type'] ?? 0),
            'code' => ($row['code'] ?? '') === '' ? null : $row['code'],
        ];

        return match ($row['kind'] ?? null) {
            'news' => ['banner_id' => null, 'code' => null] + $values,
            'banner' => ['category_id' => null, 'code' => null, 'items_limit' => 0] + $values,
            'code' => ['category_id' => null, 'banner_id' => null, 'items_limit' => 0] + $values,
            'empty' => ['category_id' => null, 'banner_id' => null, 'code' => null, 'items_limit' => 0] + $values,
            default => $values,
        };
    }

    /** 15 صندوقاً دائماً، حتى لو كانت بعضها غير محفوظة بعد. */
    private function fillBoxes($boxes)
    {
        $byPosition = $boxes->keyBy('position');

        return collect(range(1, (int) config('alnajat.home_boxes', 15)))
            ->map(fn ($position) => $byPosition->get($position) ?? new HomeBox(['position' => $position]));
    }
}
