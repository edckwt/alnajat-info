<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\News;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(int $id): View
    {
        $category = Category::where('is_active', true)->findOrFail($id);

        return view('site.list', [
            'title' => $category->name,
            'news' => News::published()
                ->with('newspaper:id,name,logo')
                ->whereHas('categories', fn ($q) => $q->whereKey($id))
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }
}
