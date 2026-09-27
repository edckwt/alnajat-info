<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\GuardsPublishing;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewsRequest;
use App\Models\Category;
use App\Models\News;
use App\Models\Newspaper;
use App\Models\PdfTemplate;
use App\Models\Publication;
use App\Services\ImageUploader;
use App\Services\PdfCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NewsController extends Controller
{
    use GuardsPublishing;

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'type' => ['nullable', 'integer'],
            'category' => ['nullable', 'integer'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:published,hidden'],
        ]);

        $news = News::query()
            ->with(['categories:id,name', 'newspaper:id,name'])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('id', (int) $term)))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['category'] ?? null, fn ($q, $cat) => $q->whereHas('categories', fn ($c) => $c->whereKey($cat)))
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->where('published_date', $date))
            ->when(($filters['status'] ?? null) === 'published', fn ($q) => $q->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'hidden', fn ($q) => $q->where('is_active', false))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.news.index', [
            'news' => $news,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'types' => config('alnajat.news_types'),
        ]);
    }

    public function create(Request $request): View
    {
        $news = new News([
            'type' => (int) $request->integer('type', 1) ?: 1,
            'published_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        // «أضف نسخة مطابقة» كما في اللوحة القديمة: نفس المحتوى بتاريخ اليوم.
        if ($from = $request->integer('duplicate')) {
            $source = News::with('categories:id')->findOrFail($from);
            $news = $source->replicate(['views', 'pdf_views', 'sort_order', 'created_by', 'updated_by', 'legacy_user_id']);
            $news->published_date = now()->toDateString();
            $news->setRelation('categories', $source->categories);
        }

        if (! array_key_exists($news->type, config('alnajat.news_types'))) {
            $news->type = 1;
        }

        return view('admin.news.form', $this->formData($news) + ['duplicateOf' => $from ?: null]);
    }

    public function store(NewsRequest $request, ImageUploader $uploader): RedirectResponse
    {
        $news = DB::transaction(function () use ($request, $uploader) {
            $news = new News;
            $this->fill($news, $request, $uploader);
            $news->created_by = $request->user()->id;
            $news->updated_by = $request->user()->id;
            $news->save();

            $this->syncCategories($news, $request);
            $this->ensurePublication($news, $request);

            return $news;
        });

        $redirect = $request->boolean('add_another')
            ? redirect()->route('admin.news.create', ['type' => $news->type])
            : redirect()->route('admin.news.edit', $news);

        return $redirect->with('status', 'تمت إضافة الخبر.');
    }

    public function edit(News $news): View
    {
        $news->load('categories:id');

        return view('admin.news.form', $this->formData($news) + ['duplicateOf' => null]);
    }

    public function update(NewsRequest $request, News $news, ImageUploader $uploader): RedirectResponse
    {
        DB::transaction(function () use ($request, $news, $uploader) {
            $this->fill($news, $request, $uploader);
            $news->updated_by = $request->user()->id;
            $news->save();

            $this->syncCategories($news, $request);
            $this->ensurePublication($news, $request);
        });

        return redirect()->route('admin.news.edit', $news)->with('status', 'تم حفظ التعديلات.');
    }

    public function toggle(News $news): RedirectResponse
    {
        $news->forceFill(['is_active' => ! $news->is_active, 'updated_by' => auth()->id()])->save();

        return back()->with('status', $news->is_active ? 'تم نشر الخبر.' : 'تم إخفاء الخبر.');
    }

    public function destroy(News $news): RedirectResponse
    {
        $title = $news->title;
        $news->delete();

        return redirect()->route('admin.news.index')->with('status', "تم حذف الخبر: {$title}");
    }

    // ------------------------------------------------------------------

    private function formData(News $news): array
    {
        $type = config('alnajat.news_types.'.$news->type);

        return [
            'news' => $news,
            'type' => $type,
            'types' => config('alnajat.news_types'),
            'categories' => Category::whereIn('id', $type['categories'])->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            // الصحف مقسومة كما في النموذج القديم: ورقية (type=0) وإلكترونية (type=1).
            'newspapers' => Newspaper::orderByDesc('id')->get(['id', 'name', 'type'])->groupBy('type'),
            'selectedCategories' => old('categories', $news->relationLoaded('categories') ? $news->categories->pluck('id')->all() : []),
        ];
    }

    private function fill(News $news, NewsRequest $request, ImageUploader $uploader): void
    {
        $data = $request->validated();
        $fields = config('alnajat.news_types.'.$data['type'].'.fields');

        $news->type = $data['type'];
        $news->title = $data['title'];
        $news->published_date = $data['published_date'];
        $news->is_active = $data['is_active'];
        $this->guardPublishing($news, 'news', $request);

        // الحقول الخاصة بالنوع فقط؛ ما لا يظهر في نموذج النوع يبقى كما هو.
        $map = [
            'description' => ['description'],
            'body' => ['body'],
            'source_url' => ['source_url'],
            'tweet_url' => ['tweet_url'],
            'sound_url' => ['sound_url'],
            'video_url' => ['video_url'],
            'newspaper' => ['newspaper_id'],
            'newspaper_number' => ['newspaper_number'],
            'pdf_flags' => ['hide_title', 'hide_description', 'hide_more', 'hide_in_pdf'],
            'image' => ['image'],
        ];

        foreach ($map as $field => $columns) {
            if (in_array($field, $fields, true)) {
                foreach ($columns as $column) {
                    $news->{$column} = $data[$column] ?? match ($column) {
                        'newspaper_number' => 0,
                        default => null,
                    };
                }
            }
        }

        if ($request->hasFile('image_file')) {
            $news->image = $uploader->store($request->file('image_file'), 'news');
        }
    }

    private function syncCategories(News $news, NewsRequest $request): void
    {
        $allowed = config('alnajat.news_types.'.$news->type.'.categories', []);

        // نوع بقسم واحد (صوتي، مرئي، تغريدة، مشروع) يُصنّف تلقائياً.
        $ids = count($allowed) === 1
            ? $allowed
            : Arr::wrap($request->validated('categories', []));

        $changes = $news->categories()->sync(Category::whereIn('id', $ids)->pluck('id'));

        // sync لا يطلق saved، وتغيير الأقسام يغيّر صفحات النشرة.
        if (array_filter($changes)) {
            app(PdfCache::class)->forgetDate($news->published_date);
        }
    }

    /** مثل publications_create القديمة: لكل تاريخ نشر نشرة واحدة. */
    private function ensurePublication(News $news, Request $request): void
    {
        if ($news->published_date === null) {
            return;
        }

        $publication = Publication::firstOrNew(['publication_date' => $news->published_date->toDateString()]);

        if (! $publication->exists) {
            $publication->forceFill([
                'title' => $news->published_date->toDateString(),
                'is_active' => true,
                'pdf_version' => (int) config('alnajat.pdf_latest_version', 5),
                'pdf_template_id' => PdfTemplate::default()?->id,
                'created_by' => $request->user()->id,
            ])->save();
        }
    }
}
