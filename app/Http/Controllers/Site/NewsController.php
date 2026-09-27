<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function show(Request $request, int $id): View
    {
        $news = News::published()->with(['categories' => fn ($q) => $q->where('is_active', true), 'newspaper'])->findOrFail($id);

        // مشاهدة واحدة لكل جلسة، ومشاهدات الـ PDF (?open=pdf) منفصلة، كما في القديم.
        $session = $request->session();
        $counts = $request->userAgent() !== \App\Console\Commands\CheckLinks::USER_AGENT; // فحص الروابط لا يُحتسب زيارة
        if ($counts && ! $session->has("seen.news.$id")) {
            $session->put("seen.news.$id", true);
            $news->timestamps = false;
            $news->increment('views');
        }
        if ($counts && $request->query('open') === 'pdf' && ! $session->has("seen.news_pdf.$id")) {
            $session->put("seen.news_pdf.$id", true);
            $news->timestamps = false;
            $news->increment('pdf_views');
        }

        return view('site.news', ['news' => $news]);
    }
}
