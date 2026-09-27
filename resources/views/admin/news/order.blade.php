<x-admin.layout title="ترتيب أخبار اليوم" :breadcrumb="['الأخبار' => route('admin.news.index'), 'ترتيب أخبار اليوم' => null]">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">ترتيب أخبار اليوم</h1>
            <p class="text-sm text-muted mt-1.5">اسحب الأخبار لترتيبها كما تظهر في الصفحة الرئيسية وفي نشرة الـ PDF.</p>
        </div>
        <form method="GET" action="{{ route('admin.news.order') }}" class="flex items-end gap-2">
            <div>
                <label class="form-label" for="date">التاريخ</label>
                <input class="form-input" id="date" name="date" type="date" value="{{ $date }}">
            </div>
            <button type="submit" class="btn btn-secondary">عرض</button>
        </form>
    </div>

    <form method="POST" action="{{ route('admin.news.order.update') }}" class="card">
        @csrf @method('PUT')
        <input type="hidden" name="date" value="{{ $date }}">

        <div class="card-header">
            <h2 class="card-title">{{ $date }} · {{ $news->count() }} خبراً</h2>
            @if ($news->isNotEmpty())
                <button type="submit" class="btn btn-primary btn-sm gap-2"><x-admin.icon name="save" class="w-4 h-4" /> حفظ الترتيب</button>
            @endif
        </div>

        @if ($news->isEmpty())
            <div class="card-body text-center text-muted py-10">لا توجد أخبار بتاريخ {{ $date }}.</div>
        @else
            <ol id="orderList" class="divide-y divide-line">
                @foreach ($news as $item)
                    <li class="flex items-center gap-3 px-5 py-3 bg-surface cursor-grab" data-id="{{ $item->id }}">
                        <input type="hidden" name="ids[]" value="{{ $item->id }}">
                        <span class="text-faint" data-handle><x-admin.icon name="grip" class="w-5 h-5" /></span>
                        <span class="w-7 text-center text-sm font-bold text-muted" data-position>{{ $loop->iteration }}</span>
                        @if ($item->thumb_url)
                            <img src="{{ $item->thumb_url }}" alt="" class="w-10 h-10 rounded-lg object-cover" loading="lazy">
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold truncate">{{ $item->title }}</p>
                            <p class="text-xs text-muted truncate">
                                {{ config('alnajat.news_types.'.$item->type.'.label') }}
                                @if ($item->categories->isNotEmpty()) · {{ $item->categories->pluck('name')->join('، ') }} @endif
                            </p>
                        </div>
                        @unless ($item->is_active)<span class="badge badge-danger">مخفي</span>@endunless
                        @if ($item->hide_in_pdf)<span class="badge badge-warning">مخفي من الـ PDF</span>@endif
                        <a href="{{ route('admin.news.edit', $item) }}" class="btn btn-icon btn-sm btn-ghost" title="تعديل"><x-admin.icon name="edit" class="w-4 h-4" /></a>
                    </li>
                @endforeach
            </ol>
        @endif
    </form>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.7/Sortable.min.js"></script>
        <script>
            (function () {
                var list = document.getElementById('orderList');
                if (!list || !window.Sortable) return;
                Sortable.create(list, {
                    animation: 150,
                    ghostClass: 'opacity-40',
                    onSort: function () {
                        list.querySelectorAll('[data-position]').forEach(function (el, i) { el.textContent = i + 1; });
                    },
                });
            })();
        </script>
    @endpush
</x-admin.layout>
