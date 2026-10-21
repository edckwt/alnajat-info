<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MissingLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** الروابط المفقودة (404): الأكثر طلباً أولاً، لإصلاحها أو إضافة تحويل لها. */
class MissingLinkController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $this->sort($request);

        return view('admin.missing-links.index', [
            'links' => $this->filtered($request)
                ->paginate(50)
                ->withQueryString(),
            'sort' => $sort,
            'ip' => $request->query('ip'),
            'total' => MissingLink::count(),
            'today' => MissingLink::where('updated_at', '>=', now()->startOfDay())->count(),
        ]);
    }

    /**
     * تصدير CSV بنفس البحث والتصفية والترتيب المعروضة (كل الصفحات). بترميز UTF-8 مع BOM
     * حتى يفتحه Excel بالعربية مباشرة، ويُقرأ من القاعدة على دفعات فلا يُحمَّل كله في الذاكرة.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request);
        $name = 'missing-links-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['الرابط', 'الرابط الكامل', 'مرات الطلب', 'جاء من', 'IP (آخر طلب)', 'أول مرة', 'آخر مرة'], escape: '');

            foreach ($query->lazy(1000) as $link) { // lazy يحفظ الترتيب المختار (lazyById يفرض الترتيب بالمعرّف)
                fputcsv($out, array_map(self::csvCell(...), [
                    $link->path,
                    url($link->path),
                    $link->hits,
                    $link->referer,
                    $link->ip,
                    $link->created_at?->format('Y-m-d H:i:s'),
                    $link->updated_at?->format('Y-m-d H:i:s'),
                ]), escape: '');
            }

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(MissingLink $missingLink): RedirectResponse
    {
        $missingLink->delete();

        return back()->with('status', 'تم حذف الرابط من القائمة.');
    }

    private function sort(Request $request): string
    {
        return $request->query('sort') === 'recent' ? 'updated_at' : 'hits';
    }

    /** البحث (الرابط أو IP) والتصفية بعنوان واحد والترتيب: مشتركة بين القائمة والتصدير. */
    private function filtered(Request $request): Builder
    {
        return MissingLink::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->query('q').'%';
                $query->where(fn ($w) => $w->where('path', 'like', $term)->orWhere('ip', 'like', $term));
            })
            ->when($request->filled('ip'), fn ($query) => $query->where('ip', $request->query('ip')))
            ->orderByDesc($this->sort($request))
            ->orderByDesc('id');
    }

    /** خلية آمنة: نص يبدأ بـ = + - @ يُفتح في Excel كصيغة، فتسبقه علامة ' (حقن الصيغ). */
    private static function csvCell(mixed $value): string|int|null
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    public function clear(): RedirectResponse
    {
        MissingLink::query()->delete();

        return redirect()->route('admin.missing-links.index')->with('status', 'تم تفريغ القائمة.');
    }
}
