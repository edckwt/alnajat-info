<?php

namespace App\Services;

use App\Models\HomeBox;
use App\Models\News;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * يبني صناديق الصفحة الرئيسية (أو النشرة) كما في index.php القديم:
 * لكل صندوق من 1 إلى 15 → بانر، أو كود، أو أخبار قسمه بقالب العرض المختار.
 *
 * الفرق عن القديم: أخبار اليوم لكل الصناديق في استعلامين بدل أكثر من 100 استعلام للصفحة.
 */
class SiteBoxes
{
    /**
     * @param  string|null  $date  أخبار هذا اليوم فقط؛ null = آخر الأخبار (?all في القديم)
     * @param  bool  $ignoreLimit  صفحة النشرة كانت تعرض كل أخبار اليوم في القسم
     * @return Collection<int, array{box: HomeBox, kind: string, posts: Collection}>
     */
    public function build(string $context, ?string $date, bool $ignoreLimit = false): Collection
    {
        $boxes = HomeBox::for($context)
            ->with(['category' => fn ($q) => $q->where('is_active', true), 'banner' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $needsNews = $boxes->filter(fn (HomeBox $box) => ! $box->banner && blank($box->code) && $box->category
            && ($ignoreLimit || $box->items_limit > 0));

        $byCategory = $date !== null
            ? $this->dayNews($needsNews->pluck('category_id')->unique()->values()->all(), $date)
            : null;

        $needsIds = $needsNews->pluck('id')->flip();

        return $boxes
            ->map(function (HomeBox $box) use ($needsIds, $byCategory, $ignoreLimit) {
                if ($box->banner) {
                    return ['box' => $box, 'kind' => 'banner', 'posts' => collect()];
                }

                if (filled($box->code)) {
                    return ['box' => $box, 'kind' => 'code', 'posts' => collect()];
                }

                if (! $needsIds->has($box->id)) {
                    return null;
                }

                $posts = $byCategory !== null
                    ? ($byCategory[$box->category_id] ?? collect())
                    : $this->latestNews($box->category_id, $box->items_limit);

                if (! $ignoreLimit) {
                    $posts = $posts->take($box->items_limit);
                }

                return $posts->isEmpty() ? null : ['box' => $box, 'kind' => 'news', 'posts' => $posts->values()];
            })
            ->filter()
            ->values();
    }

    /**
     * أخبار يوم واحد لكل الأقسام المطلوبة باستعلامين (الأخبار + ربطها بالأقسام)
     * بدل استعلام لكل صندوق. أخبار اليوم الواحد عشرات فقط.
     *
     * @param  list<int>  $categoryIds
     * @return array<int, Collection<int, News>>  رقم القسم => أخباره بالأحدث
     */
    private function dayNews(array $categoryIds, string $date): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $news = News::published()
            ->with('newspaper:id,name,logo')
            ->where('published_date', $date)
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
            ->orderByDesc('id')
            ->get()
            ->keyBy('id');

        if ($news->isEmpty()) {
            return [];
        }

        $grouped = [];
        DB::table('category_news')
            ->whereIn('news_id', $news->keys())
            ->whereIn('category_id', $categoryIds)
            ->get(['news_id', 'category_id'])
            ->each(function ($link) use (&$grouped) {
                $grouped[(int) $link->category_id][] = (int) $link->news_id;
            });

        return array_map(
            fn (array $ids) => collect($ids)->sortDesc()->map(fn (int $id) => $news[$id])->values(),
            $grouped,
        );
    }

    /** ?all: آخر N خبر في القسم بلا تقيد بيوم (نادر الاستخدام، استعلام لكل صندوق). */
    private function latestNews(int $categoryId, int $limit): Collection
    {
        return News::published()
            ->with('newspaper:id,name,logo')
            ->whereHas('categories', fn ($q) => $q->whereKey($categoryId))
            ->orderByDesc('id')
            ->limit(max($limit, 1))
            ->get();
    }
}
