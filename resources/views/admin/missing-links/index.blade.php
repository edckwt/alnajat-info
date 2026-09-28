<x-admin.layout title="روابط مفقودة" :breadcrumb="['روابط مفقودة' => null]">
    <x-admin.page-header title="روابط مفقودة (404)"
                         :subtitle="'روابط طلبها الزوار ولم تُوجد. '.number_format($total).' رابطاً، منها '.number_format($today).' طُلب اليوم.'">
        @if ($total > 0 && auth()->user()->can('missing_links.delete'))
            <form method="POST" action="{{ route('admin.missing-links.clear') }}" data-confirm="تفريغ قائمة الروابط المفقودة كلها؟" data-confirm-title="تفريغ القائمة" data-confirm-ok="تفريغ">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline gap-2"><x-admin.icon name="trash" class="w-4 h-4" /> تفريغ القائمة</button>
            </form>
        @endif
    </x-admin.page-header>

    <div class="card">
        <form method="GET" class="card-body flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-56">
                <input type="search" name="q" value="{{ request('q') }}" class="form-input" dir="ltr" placeholder="ابحث في الرابط أو IP…">
            </div>
            @if ($ip)
                <input type="hidden" name="ip" value="{{ $ip }}">
                <a href="{{ request()->fullUrlWithoutQuery(['ip', 'page']) }}" class="badge badge-primary gap-1.5" title="إلغاء التصفية">IP: <span dir="ltr">{{ $ip }}</span> <x-admin.icon name="x" class="w-3 h-3" /></a>
            @endif
            <select name="sort" class="form-select w-auto" onchange="this.form.submit()">
                <option value="hits" @selected($sort === 'hits')>الأكثر طلباً</option>
                <option value="recent" @selected($sort === 'updated_at')>الأحدث</option>
            </select>
            <button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="search" class="w-4 h-4" /> بحث</button>
        </form>

        <div class="table-wrap">
            <table class="table table-hover">
                <thead><tr class="border-b border-line"><th>الرابط</th><th class="text-center">مرات الطلب</th><th>جاء من</th><th>IP (آخر طلب)</th><th>أول مرة</th><th>آخر مرة</th><th class="text-center">حذف</th></tr></thead>
                <tbody>
                    @forelse ($links as $link)
                        <tr>
                            <td class="max-w-md">
                                <a href="{{ url($link->path) }}" target="_blank" rel="noopener" dir="ltr" class="font-semibold text-primary-600 hover:underline break-all">{{ $link->path }}</a>
                            </td>
                            <td class="text-center font-bold">{{ number_format($link->hits) }}</td>
                            <td class="max-w-xs text-sm text-muted break-all" dir="ltr">{{ $link->referer ?: '—' }}</td>
                            <td class="text-sm whitespace-nowrap" dir="ltr">
                                @if ($link->ip)
                                    <a href="{{ request()->fullUrlWithQuery(['ip' => $link->ip, 'page' => null]) }}" class="font-mono text-ink hover:text-primary-600 hover:underline" title="كل الروابط من هذا العنوان">{{ $link->ip }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted text-sm whitespace-nowrap">{{ $link->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="text-muted text-sm whitespace-nowrap">{{ $link->updated_at?->format('Y-m-d H:i') }}</td>
                            <td><x-admin.actions :destroy="auth()->user()->can('missing_links.delete') ? route('admin.missing-links.destroy', $link) : null" confirm="حذف هذا الرابط من القائمة؟" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-10">لا توجد روابط مفقودة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($links->hasPages())
            <div class="card-footer flex justify-end">{{ $links->links('admin.partials.pagination') }}</div>
        @endif
    </div>
</x-admin.layout>
