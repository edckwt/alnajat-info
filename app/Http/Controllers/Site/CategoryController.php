<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /** طرق عرض أخبار القسم؛ الأولى هي الافتراضية. */
    public const LAYOUTS = ['grid', 'list'];

    public const LAYOUT_COOKIE = 'news_layout';

    public function show(Request $request, int $id): View
    {
        $category = Category::where('is_active', true)->findOrFail($id);

        return view('site.list', [
            'title' => $category->name,
            'layout' => $this->layout($request),
            'news' => News::published()
                ->with('newspaper:id,name,logo')
                ->whereHas('categories', fn ($q) => $q->whereKey($id))
                ->orderByDesc('id')
                ->paginate(15)
                ->appends($request->only('view')),
        ]);
    }

    /** شبكي (الافتراضي) أو قائمة: من ?view= ثم يُحفظ اختيار الزائر في كوكي لسنة. */
    private function layout(Request $request): string
    {
        $chosen = $request->query('view');
        if (is_string($chosen) && in_array($chosen, self::LAYOUTS, true)) {
            Cookie::queue(self::LAYOUT_COOKIE, $chosen, 60 * 24 * 365);

            return $chosen;
        }

        $saved = $request->cookie(self::LAYOUT_COOKIE);

        return in_array($saved, self::LAYOUTS, true) ? $saved : self::LAYOUTS[0];
    }
}
