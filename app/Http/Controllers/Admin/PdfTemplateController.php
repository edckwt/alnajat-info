<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PdfTemplate;
use App\Models\Publication;
use App\Services\ImageUploader;
use App\Services\PdfBuilder;
use App\Services\PdfTemplateFactory;
use App\Services\PdfTemplates;
use App\Support\PdfDesign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * قوالب النشرة: القديمة (للاطلاع والنسخ منها) والمصمَّمة بالسحب والإفلات.
 * المسودة تُعدّل؛ المعتمد لا يُعدّل حتى لا يتغير شكل النشرات التي تستخدمه، بل يُنسخ.
 */
class PdfTemplateController extends Controller
{
    public function index(PdfTemplates $legacy): View
    {
        return view('admin.pdf-templates.index', [
            'templates' => PdfTemplate::withCount('publications')->orderByDesc('is_default')->orderByDesc('id')->get(),
            'legacy' => array_reverse($legacy->all(), true),
        ]);
    }

    public function create(PdfTemplates $legacy): View
    {
        return view('admin.pdf-templates.create', [
            'legacy' => array_reverse($legacy->all(), true),
            'templates' => PdfTemplate::orderByDesc('id')->get(['id', 'name', 'status']),
        ]);
    }

    /** إنشاء مسودة: فارغة، أو من قالب قديم، أو نسخة من قالب مصمَّم. */
    public function store(Request $request, PdfTemplateFactory $factory): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'from' => ['required', 'string', 'regex:/^(blank|v\d+|t\d+)$/'],
        ], [], ['name' => 'اسم القالب', 'from' => 'البداية']);

        $template = new PdfTemplate(['name' => $data['name']]);
        $from = $data['from'];

        if ($from === 'blank') {
            $template->design = $factory->blank();
        } elseif ($from[0] === 'v') {
            $version = (int) substr($from, 1);
            abort_unless($version >= 1 && $version <= (int) config('alnajat.pdf_latest_version'), 422);
            $template->design = $factory->fromLegacy($version);
            $template->based_on_version = $version;
        } else {
            $source = PdfTemplate::findOrFail((int) substr($from, 1));
            $template->design = $source->design;
            $template->based_on_id = $source->id;
        }

        $template->status = PdfTemplate::DRAFT;
        $template->created_by = $template->updated_by = $request->user()->id;
        $template->save();

        return redirect()->route('admin.pdf-templates.edit', $template)->with('status', 'تم إنشاء المسودة؛ صمّمها ثم اعتمدها.');
    }

    /** المحرر (للمسودة)، أو عرض للقراءة فقط (للمعتمد). */
    public function edit(Request $request, PdfTemplate $pdfTemplate): View
    {
        return view('admin.pdf-templates.design', [
            'template' => $pdfTemplate,
            'design' => $pdfTemplate->toDesign()->toArray(),
            'readonly' => ! $pdfTemplate->isEditable() || ! $request->user()->can('pdf_templates.update'),
            'fonts' => $this->fonts(),
            'variables' => PdfDesign::VARIABLES,
            'formats' => array_keys(PdfDesign::FORMATS),
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'publications' => Publication::orderByDesc('publication_date')->limit(30)->get(['id', 'title', 'publication_date']),
        ]);
    }

    /** حفظ التصميم (JSON من المحرر). */
    public function update(Request $request, PdfTemplate $pdfTemplate): JsonResponse
    {
        abort_unless($pdfTemplate->isEditable(), 409, 'القالب معتمد؛ انسخه لتعديله.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'design' => ['required', 'array'],
        ], [], ['name' => 'اسم القالب', 'design' => 'التصميم']);

        $pdfTemplate->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'design' => PdfDesign::fromArray($data['design'])->toArray(),
        ]);
        $pdfTemplate->updated_by = $request->user()->id;
        $pdfTemplate->save();

        return response()->json(['ok' => true, 'savedAt' => $pdfTemplate->updated_at?->format('H:i'), 'design' => $pdfTemplate->design]);
    }

    public function destroy(PdfTemplate $pdfTemplate): RedirectResponse
    {
        if ($pdfTemplate->publications()->exists()) {
            return back()->withErrors(['template' => "القالب «{$pdfTemplate->name}» مستخدم في نشرات؛ لا يمكن حذفه."]);
        }

        if ($pdfTemplate->is_default) {
            return back()->withErrors(['template' => 'لا يمكن حذف القالب الافتراضي؛ اختر غيره افتراضياً أولاً.']);
        }

        $pdfTemplate->delete();

        return redirect()->route('admin.pdf-templates.index')->with('status', "تم حذف القالب: {$pdfTemplate->name}");
    }

    /** اعتماد المسودة: تصبح متاحة للنشرات ولا تُعدّل بعدها. */
    public function publish(Request $request, PdfTemplate $pdfTemplate): RedirectResponse
    {
        $pdfTemplate->forceFill([
            'status' => PdfTemplate::PUBLISHED,
            'published_at' => now(),
            'updated_by' => $request->user()->id,
        ])->save();

        return redirect()->route('admin.pdf-templates.index')->with('status', "تم اعتماد القالب: {$pdfTemplate->name}");
    }

    /** القالب الافتراضي للنشرات الجديدة (معتمد فقط)، أو إلغاء الافتراضي للرجوع للقالب القديم الأحدث. */
    public function makeDefault(Request $request, PdfTemplate $pdfTemplate): RedirectResponse
    {
        abort_unless($pdfTemplate->isPublished(), 422, 'اعتمد القالب أولاً.');

        $turnOn = ! $pdfTemplate->is_default;

        DB::transaction(function () use ($pdfTemplate, $turnOn) {
            PdfTemplate::where('is_default', true)->update(['is_default' => false]);
            if ($turnOn) {
                $pdfTemplate->forceFill(['is_default' => true])->save();
            }
        });

        return back()->with('status', $turnOn
            ? "أصبح «{$pdfTemplate->name}» القالب الافتراضي للنشرات الجديدة."
            : 'النشرات الجديدة تعود للقالب القديم الأحدث.');
    }

    public function duplicate(Request $request, PdfTemplate $pdfTemplate): RedirectResponse
    {
        $copy = new PdfTemplate([
            'name' => $pdfTemplate->name.' (نسخة)',
            'description' => $pdfTemplate->description,
            'design' => $pdfTemplate->design,
        ]);
        $copy->status = PdfTemplate::DRAFT;
        $copy->based_on_id = $pdfTemplate->id;
        $copy->created_by = $copy->updated_by = $request->user()->id;
        $copy->save();

        return redirect()->route('admin.pdf-templates.edit', $copy)->with('status', 'تم إنشاء نسخة قابلة للتعديل.');
    }

    /**
     * معاينة التصميم الحالي في المحرر (قبل الحفظ) بأخبار نشرة حقيقية:
     * الغلاف وأول ثلاثة أقسام بخبر واحد لكل قسم ثم الختام.
     */
    public function preview(Request $request, PdfTemplate $pdfTemplate, PdfBuilder $pdf): Response
    {
        $data = $request->validate([
            'design' => ['nullable', 'array'],
            'publication' => ['nullable', 'integer', 'exists:publications,id'],
        ]);

        $design = PdfDesign::fromArray($data['design'] ?? $pdfTemplate->design ?? []);
        $publication = filled($data['publication'] ?? null)
            ? Publication::findOrFail($data['publication'])
            : (Publication::published()->whereHas('news')->orderByDesc('publication_date')->first() ?? new Publication(['title' => 'معاينة', 'publication_date' => now()->toDateString()]));

        @set_time_limit(120);

        return response($pdf->publication($publication, sample: 3, design: $design), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="template-preview.pdf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** رفع صورة للقالب (خلفية أو عنصر صورة) بالسحب والإفلات في المحرر. */
    public function upload(Request $request, PdfTemplate $pdfTemplate, ImageUploader $uploader): JsonResponse
    {
        abort_unless($pdfTemplate->isEditable(), 409);

        $request->validate(['image' => ['required', 'image', 'max:'.config('alnajat.image_max_kb')]], [], ['image' => 'الصورة']);

        $path = $uploader->store($request->file('image'), 'pdf-template');

        return response()->json(['path' => $path, 'url' => \App\Support\Media::url($path)]);
    }

    /** @return array<string,string> الخطوط المتاحة فعلاً (ملفاتها في resources/fonts) */
    private function fonts(): array
    {
        return collect(config('alnajat.pdf.fonts'))
            ->filter(fn ($files) => is_file(config('alnajat.pdf.fonts_path').'/'.$files['R']))
            ->mapWithKeys(fn ($files, $font) => [$font => SettingController::PDF_FONTS[$font] ?? \Illuminate\Support\Str::headline($font)])
            ->all();
    }
}
