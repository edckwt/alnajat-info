<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MissingLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** الروابط المفقودة (404): الأكثر طلباً أولاً، لإصلاحها أو إضافة تحويل لها. */
class MissingLinkController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->query('sort') === 'recent' ? 'updated_at' : 'hits';

        return view('admin.missing-links.index', [
            'links' => MissingLink::query()
                ->when($request->filled('q'), function ($query) use ($request) {
                    $term = '%'.$request->query('q').'%';
                    $query->where(fn ($w) => $w->where('path', 'like', $term)->orWhere('ip', 'like', $term));
                })
                ->when($request->filled('ip'), fn ($query) => $query->where('ip', $request->query('ip')))
                ->orderByDesc($sort)
                ->orderByDesc('id')
                ->paginate(50)
                ->withQueryString(),
            'sort' => $sort,
            'ip' => $request->query('ip'),
            'total' => MissingLink::count(),
            'today' => MissingLink::where('updated_at', '>=', now()->startOfDay())->count(),
        ]);
    }

    public function destroy(MissingLink $missingLink): RedirectResponse
    {
        $missingLink->delete();

        return back()->with('status', 'تم حذف الرابط من القائمة.');
    }

    public function clear(): RedirectResponse
    {
        MissingLink::query()->delete();

        return redirect()->route('admin.missing-links.index')->with('status', 'تم تفريغ القائمة.');
    }
}
