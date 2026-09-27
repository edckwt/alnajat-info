<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\GuardsPublishing;
use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\PdfTemplate;
use App\Models\Publication;
use App\Services\ImageUploader;
use App\Services\PdfBuilder;
use App\Services\PdfTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * النشرة = أخبار يوم واحد (publication_date). تُنشأ تلقائياً عند حفظ أول خبر
 * بتاريخها، ويمكن إنشاؤها يدوياً هنا لتعديل غلافها قبل إضافة الأخبار.
 */
class PublicationController extends Controller
{
    use GuardsPublishing;

    public function index(Request $request): View
    {
        $publications = Publication::query()
            ->when($request->filled('year'), fn ($q) => $q->whereYear('publication_date', $request->integer('year')))
            ->orderByDesc('publication_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        // عدد الأخبار لكل نشرة باستعلام واحد (العلاقة بالتاريخ لا بمفتاح).
        $counts = News::query()
            ->whereIn('published_date', $publications->pluck('publication_date')->filter()->map->toDateString())
            ->selectRaw('published_date, count(*) as total')
            ->groupBy('published_date')
            ->pluck('total', 'published_date');

        return view('admin.publications.index', [
            'publications' => $publications,
            'counts' => $counts->mapWithKeys(fn ($total, $date) => [substr((string) $date, 0, 10) => $total]),
        ]);
    }

    public function create(PdfTemplates $templates): View
    {
        return view('admin.publications.form', [
            'publication' => new Publication([
                'is_active' => true,
                'publication_date' => now()->toDateString(),
                'pdf_version' => config('alnajat.pdf_latest_version'),
                'pdf_template_id' => PdfTemplate::default()?->id,
            ]),
            'newsCount' => 0,
            'templates' => $templates->all(),
            'designs' => $this->designs(),
        ]);
    }

    public function store(Request $request, ImageUploader $uploader): RedirectResponse
    {
        $publication = new Publication;
        $this->save($publication, $request, $uploader);

        return redirect()->route('admin.publications.edit', $publication)->with('status', 'تم إنشاء النشرة.');
    }

    public function edit(Publication $publication, PdfTemplates $templates): View
    {
        return view('admin.publications.form', [
            'publication' => $publication,
            'templates' => $templates->all(),
            'designs' => $this->designs($publication->pdf_template_id),
            'newsCount' => $publication->publication_date
                ? News::where('published_date', $publication->publication_date->toDateString())->count()
                : 0,
        ]);
    }

    public function update(Request $request, Publication $publication, ImageUploader $uploader): RedirectResponse
    {
        $this->save($publication, $request, $uploader);

        return redirect()->route('admin.publications.edit', $publication)->with('status', 'تم حفظ النشرة.');
    }

    /** القوالب المصمَّمة المعتمدة (وقالب النشرة الحالي حتى لو لم يعد معتمداً). */
    private function designs(?int $current = null)
    {
        return PdfTemplate::query()
            ->where(fn ($q) => $q->where('status', PdfTemplate::PUBLISHED)->when($current, fn ($q) => $q->orWhere('id', $current)))
            ->withCount('publications')
            ->orderByDesc('is_default')->orderByDesc('id')
            ->get();
    }

    /**
     * معاينة سريعة للنشرة بقالب معيّن قبل الحفظ: الغلاف وأول ثلاثة أقسام بخبر واحد والخاتمة.
     * ?publication= نشرة محفوظة (اختياري)، ?date= تاريخ الأخبار، ?version= القالب.
     */
    public function preview(Request $request, PdfBuilder $pdf): Response
    {
        $validator = Validator::make($request->query(), [
            'publication' => ['nullable', 'integer'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'version' => ['required_without:template', 'nullable', 'integer', 'between:1,'.config('alnajat.pdf_latest_version')],
            'template' => ['nullable', 'integer'],
        ]);
        abort_if($validator->fails(), 404);
        $data = $validator->validated();
        $design = filled($data['template'] ?? null) ? PdfTemplate::findOrFail($data['template'])->toDesign() : null;

        $publication = filled($data['publication'] ?? null)
            ? Publication::findOrFail($data['publication'])
            : new Publication(['title' => 'معاينة']);

        // تاريخ النموذج قبل الحفظ (لا يُحفظ شيء هنا)
        if (filled($data['date'] ?? null)) {
            $publication->publication_date = $data['date'];
        }
        $publication->publication_date ??= now()->toDateString();

        @set_time_limit(120);

        $version = $design ? null : (int) $data['version'];

        return response($pdf->publication($publication, $version, sample: 3, design: $design), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function toggle(Publication $publication): RedirectResponse
    {
        $publication->update(['is_active' => ! $publication->is_active]);

        return back()->with('status', 'تم تحديث حالة النشرة.');
    }

    public function destroy(Publication $publication): RedirectResponse
    {
        // حذف النشرة لا يحذف أخبار يومها.
        $publication->delete();

        return redirect()->route('admin.publications.index')->with('status', 'تم حذف النشرة.');
    }

    private function save(Publication $publication, Request $request, ImageUploader $uploader): void
    {
        $data = $request->validate([
            'publication_date' => ['required', 'date_format:Y-m-d',
                Rule::unique('publications', 'publication_date')->ignore($publication->id)],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'url' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'cover' => ['boolean'],
            'pdf_version' => ['required', 'integer', 'between:1,'.config('alnajat.pdf_latest_version')],
            // قالب مصمَّم معتمد (أو القالب الحالي للنشرة وإن لم يعد معتمداً)
            'pdf_template_id' => ['nullable', 'integer', Rule::exists('pdf_templates', 'id')->where(fn ($q) => $q->where('status', PdfTemplate::PUBLISHED)->orWhere('id', $publication->pdf_template_id ?? 0))],
            'is_active' => ['boolean'],
            'image_file' => ['nullable', 'image', 'max:'.config('alnajat.image_max_kb')],
            'other_file_upload' => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
        ], [
            'publication_date.unique' => 'توجد نشرة بهذا التاريخ.',
        ], [
            'publication_date' => 'تاريخ النشرة', 'title' => 'العنوان', 'image_file' => 'صورة الغلاف',
            'other_file_upload' => 'ملحق النشرة', 'pdf_version' => 'نسخة القالب', 'pdf_template_id' => 'القالب',
        ]);

        $publication->fill([
            'publication_date' => $data['publication_date'],
            'title' => ($data['title'] ?? null) ?: $data['publication_date'],
            'description' => $data['description'] ?? null,
            'url' => $data['url'] ?? null,
            'body' => $data['body'] ?? null,
            'cover' => (int) ($data['cover'] ?? 0),
            'pdf_version' => $data['pdf_version'],
            'pdf_template_id' => $data['pdf_template_id'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        if ($request->hasFile('image_file')) {
            $publication->image = $uploader->store($request->file('image_file'), 'publications');
        }

        if ($request->hasFile('other_file_upload')) {
            $publication->other_file = $uploader->store($request->file('other_file_upload'), 'publications');
        } elseif ($request->boolean('remove_other_file')) {
            $publication->other_file = null;
        }

        $this->guardPublishing($publication, 'publications', $request);

        $publication->forceFill([
            'created_by' => $publication->created_by ?? $request->user()->id,
            'updated_by' => $request->user()->id,
        ])->save();
    }
}
