<x-admin.layout title="الأخبار" :breadcrumb="['الأخبار' => null]">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">الأخبار</h1>
            <p class="text-sm text-muted mt-1.5">{{ number_format($news->total()) }} خبراً</p>
        </div>
        @can('news.create')
        <div class="dropdown">
            <button type="button" class="btn btn-primary gap-2" data-dropdown-toggle aria-expanded="false">
                <x-admin.icon name="plus" class="w-4 h-4" /> <span>إضافة</span>
                <x-admin.icon name="chevronDown" class="w-3.5 h-3.5" />
            </button>
            <div class="dropdown-menu end-0 !min-w-[12rem]" data-dropdown-menu hidden>
                @foreach ($types as $id => $type)
                    <a href="{{ route('admin.news.create', ['type' => $id]) }}" class="dropdown-item">
                        <x-admin.icon :name="$type['icon']" /><span>{{ $type['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
        @endcan
    </div>

    <form method="GET" action="{{ route('admin.news.index') }}" class="card card-body">
        <div class="grid grid-cols-12 gap-4 items-end">
            <div class="col-span-12 lg:col-span-4">
                <label class="form-label" for="q">بحث</label>
                <div class="input-group">
                    <span class="input-group-icon"><x-admin.icon name="search" class="w-4 h-4" /></span>
                    <input class="form-input" id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="العنوان أو الوصف أو رقم الخبر">
                </div>
            </div>
            <div class="col-span-6 sm:col-span-3 lg:col-span-2">
                <label class="form-label" for="type">النوع</label>
                <select class="form-select" id="type" name="type">
                    <option value="">الكل</option>
                    @foreach ($types as $id => $type)
                        <option value="{{ $id }}" @selected(($filters['type'] ?? null) == $id)>{{ $type['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-6 sm:col-span-3 lg:col-span-2">
                <label class="form-label" for="category">القسم</label>
                <select class="form-select" id="category" name="category">
                    <option value="">الكل</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category'] ?? null) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-6 sm:col-span-3 lg:col-span-2">
                <label class="form-label" for="date">تاريخ النشر</label>
                <input class="form-input" id="date" name="date" type="date" value="{{ $filters['date'] ?? '' }}">
            </div>
            <div class="col-span-6 sm:col-span-3 lg:col-span-2">
                <label class="form-label" for="status">الحالة</label>
                <select class="form-select" id="status" name="status">
                    <option value="">الكل</option>
                    <option value="published" @selected(($filters['status'] ?? null) === 'published')>منشور</option>
                    <option value="hidden" @selected(($filters['status'] ?? null) === 'hidden')>مخفي</option>
                </select>
            </div>
            <div class="col-span-12 flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">تصفية</button>
                @if (array_filter($filters))
                    <a href="{{ route('admin.news.index') }}" class="btn btn-secondary btn-sm">مسح التصفية</a>
                @endif
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="table table-hover">
                <thead>
                    <tr class="border-b border-line">
                        <th class="w-16">الصورة</th>
                        <th class="min-w-[16rem]">العنوان</th>
                        <th>النوع</th>
                        <th>الأقسام</th>
                        <th>تاريخ النشر</th>
                        <th>المشاهدات</th>
                        <th>الحالة</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($news as $item)
                        <tr>
                            <td>
                                @if ($item->thumb_url)
                                    <img src="{{ $item->thumb_url }}" alt="" class="w-12 h-12 rounded-lg object-cover" loading="lazy">
                                @else
                                    <span class="w-12 h-12 rounded-lg bg-surface-2 grid place-items-center text-faint">
                                        <x-admin.icon name="image" class="w-5 h-5" />
                                    </span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.news.edit', $item) }}" class="font-semibold text-ink hover:text-primary-600">{{ $item->title }}</a>
                                <p class="text-xs text-muted mt-0.5">
                                    #{{ $item->id }}
                                    @if ($item->newspaper) · {{ $item->newspaper->name }} @endif
                                    @if ($item->hide_in_pdf) · <span class="text-warning-600">مخفي من الـ PDF</span> @endif
                                </p>
                            </td>
                            <td><span class="badge badge-muted">{{ $types[$item->type]['label'] ?? $item->type }}</span></td>
                            <td class="text-muted text-sm">{{ $item->categories->pluck('name')->join('، ') }}</td>
                            <td class="text-muted text-sm whitespace-nowrap">{{ $item->published_date?->format('Y-m-d') }}</td>
                            <td class="text-muted text-sm">{{ number_format($item->views) }}</td>
                            <td><x-admin.status permission="news" :record="$item" :active="$item->is_active" :toggle="route('admin.news.toggle', $item)" /></td>
                            <td>
                                <x-admin.actions permission="news" :record="$item" :edit="route('admin.news.edit', $item)" :destroy="route('admin.news.destroy', $item)"
                                                 :confirm="'حذف الخبر «'.$item->title.'» نهائياً؟'">
                                    @can('news.create')
                                        <a href="{{ route('admin.news.create', ['duplicate' => $item->id]) }}" class="btn btn-icon btn-sm btn-ghost" title="نسخة مطابقة بتاريخ اليوم"><x-admin.icon name="copy" class="w-4 h-4" /></a>
                                    @endcan
                                </x-admin.actions>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-10">لا توجد أخبار مطابقة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($news->hasPages())
            <div class="card-footer flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-muted">{{ $news->firstItem() }}–{{ $news->lastItem() }} من {{ number_format($news->total()) }}</p>
                {{ $news->links('admin.partials.pagination') }}
            </div>
        @endif
    </div>
</x-admin.layout>
