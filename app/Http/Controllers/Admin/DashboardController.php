<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Publication;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->toDateString();

        return view('admin.dashboard', [
            'stats' => [
                ['label' => __('admin.dashboard.today_news'), 'value' => News::where('published_date', $today)->count(), 'icon' => 'calendar', 'tone' => 'bg-primary-500/12 text-primary-600'],
                ['label' => __('admin.dashboard.published_news'), 'value' => News::published()->count(), 'icon' => 'news', 'tone' => 'bg-success-500/12 text-success-500'],
                ['label' => __('admin.dashboard.publications'), 'value' => Publication::count(), 'icon' => 'pdf', 'tone' => 'bg-warning-500/12 text-warning-500'],
                ['label' => __('admin.dashboard.views'), 'value' => (int) News::sum('views'), 'icon' => 'eye', 'tone' => 'bg-info-500/12 text-info-500'],
            ],
            'latestNews' => News::with('categories:id,name')
                ->latest('id')
                ->limit(10)
                ->get(['id', 'title', 'published_date', 'is_active']),
        ]);
    }
}
