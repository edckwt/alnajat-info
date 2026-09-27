<x-admin.layout title="الصحف" :breadcrumb="['الصحف' => null]">
    <x-admin.page-header title="الصحف" subtitle="مصادر الأخبار: صحف ورقية ومواقع إلكترونية.">
        @can('newspapers.create')
            <a href="{{ route('admin.newspapers.create') }}" class="btn btn-primary gap-2"><x-admin.icon name="plus" class="w-4 h-4" /> صحيفة جديدة</a>
        @endcan
    </x-admin.page-header>

    <div class="tabs-pill">
        <a href="{{ route('admin.newspapers.index') }}" class="tab" aria-selected="{{ request()->filled('type') ? 'false' : 'true' }}">الكل</a>
        @foreach ($types as $id => $label)
            <a href="{{ route('admin.newspapers.index', ['type' => $id]) }}" class="tab" aria-selected="{{ request()->filled('type') && request('type') == $id ? 'true' : 'false' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table table-hover">
                <thead><tr class="border-b border-line"><th class="w-16">الشعار</th><th>الاسم</th><th>النوع</th><th>الأخبار</th><th>الحالة</th><th class="text-center">إجراءات</th></tr></thead>
                <tbody>
                    @forelse ($newspapers as $paper)
                        <tr>
                            <td>
                                @if ($paper->logo)
                                    <img src="{{ \App\Support\Media::url($paper->logo) }}" alt="" class="w-12 h-12 rounded-lg object-contain bg-surface-2">
                                @endif
                            </td>
                            <td class="font-semibold">{{ $paper->name }}
                                @if ($paper->url)<a href="{{ $paper->url }}" target="_blank" class="block text-xs text-muted truncate" dir="ltr">{{ $paper->url }}</a>@endif
                            </td>
                            <td><span class="badge badge-muted">{{ $types[$paper->type] ?? $paper->type }}</span></td>
                            <td class="text-muted">{{ number_format($paper->news_count) }}</td>
                            <td><x-admin.status permission="newspapers" :active="$paper->is_active" :toggle="route('admin.newspapers.toggle', $paper)" on="ظاهرة" off="مخفية" /></td>
                            <td><x-admin.actions permission="newspapers" :edit="route('admin.newspapers.edit', $paper)" :destroy="route('admin.newspapers.destroy', $paper)"
                                                 :confirm="'حذف صحيفة «'.$paper->name.'»؟ أخبارها تبقى بلا صحيفة.'" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-10">لا توجد صحف.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin.layout>
