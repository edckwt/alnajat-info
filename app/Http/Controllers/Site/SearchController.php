<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request, ?string $q = null): View
    {
        $term = trim(strip_tags((string) ($q ?? $request->query('s', ''))));

        $news = $term === ''
            ? null
            : News::published()
                ->with('newspaper:id,name,logo')
                ->where(fn ($w) => $w->where('title', 'like', "%{$term}%")->orWhere('body', 'like', "%{$term}%"))
                ->orderByDesc('id')
                ->paginate(10)
                ->withPath(url('search/'.rawurlencode($term)));

        return view('site.search', ['term' => $term, 'news' => $news]);
    }
}
