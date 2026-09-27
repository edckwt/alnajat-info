<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\PdfCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ترتيب أخبار يوم معيّن. الموقع العام والـ PDF يعرضانها بترتيب sort_order تصاعدياً
 * (orders في النظام القديم).
 */
class NewsOrderController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']])['date'] ?? now()->toDateString();

        return view('admin.news.order', [
            'date' => $date,
            'news' => News::with('categories:id,name')
                ->where('published_date', $date)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'title', 'image', 'type', 'is_active', 'hide_in_pdf', 'sort_order']),
        ]);
    }

    public function update(Request $request, PdfCache $pdf): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        DB::transaction(function () use ($data) {
            foreach (array_values($data['ids']) as $position => $id) {
                News::whereKey($id)
                    ->where('published_date', $data['date'])
                    ->update(['sort_order' => $position + 1]);
            }
        });

        // التحديث الجماعي لا يطلق أحداث الموديل.
        $pdf->forgetDate($data['date']);

        return redirect()
            ->route('admin.news.order', ['date' => $data['date']])
            ->with('status', 'تم حفظ الترتيب.');
    }
}
