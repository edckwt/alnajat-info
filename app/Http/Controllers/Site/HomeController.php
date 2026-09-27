<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Services\SiteBoxes;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request, SiteBoxes $boxes): View
    {
        // ?all = آخر الأخبار بلا تقيد بيوم، ?date=Y-m-d = يوم محدد، وإلا اليوم.
        $date = match (true) {
            $request->has('all') => null,
            preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date')) === 1 => $request->query('date'),
            default => now()->toDateString(),
        };

        return view('site.home', [
            'boxes' => $boxes->build('home', $date),
            'publication' => Publication::published()->orderByDesc('publication_date')->first(),
        ]);
    }
}
