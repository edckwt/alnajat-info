<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Services\SiteBoxes;
use Illuminate\View\View;

class PublicationController extends Controller
{
    /** أرشيف النشرات (archive.html). */
    public function index(): View
    {
        return view('site.archive', [
            'publications' => Publication::published()->orderByDesc('publication_date')->orderByDesc('id')->paginate(20),
        ]);
    }

    /** صفحة نشرة: غلافها ثم صناديق الرئيسية بأخبار يومها. */
    public function show(int $id, SiteBoxes $boxes): View
    {
        $publication = Publication::published()->findOrFail($id);

        return view('site.publication', [
            'publication' => $publication,
            'boxes' => $publication->publication_date
                ? $boxes->build('home', $publication->publication_date->toDateString(), ignoreLimit: true)
                : collect(),
        ]);
    }
}
