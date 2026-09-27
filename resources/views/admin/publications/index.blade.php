<x-admin.layout title="النشرات" :breadcrumb="['النشرات' => null]">
    <x-admin.page-header title="النشرات" subtitle="نشرة لكل يوم، تجمع أخبار تاريخها. تُنشأ تلقائياً عند إضافة أول خبر بالتاريخ.">
        @can('publications.create')
            <a href="{{ route('admin.publications.create') }}" class="btn btn-primary gap-2"><x-admin.icon name="plus" class="w-4 h-4" /> نشرة جديدة</a>
        @endcan
    </x-admin.page-header>

    <div class="card">
        <div class="table-wrap">
            <table class="table table-hover">
                <thead><tr class="border-b border-line"><th>#</th><th>التاريخ</th><th>العنوان</th><th>الأخبار</th><th>القالب</th><th>PDF</th><th>الحالة</th><th class="text-center">إجراءات</th></tr></thead>
                <tbody>
                    @forelse ($publications as $publication)
                        @php($date = $publication->publication_date?->toDateString())
                        <tr>
                            <td class="text-muted">{{ $publication->id }}</td>
                            <td class="font-semibold whitespace-nowrap">{{ $date ?? '—' }}</td>
                            <td>{{ $publication->title }}</td>
                            <td>
                                @if ($date)
                                    <a class="text-primary-600 hover:underline" href="{{ route('admin.news.index', ['date' => $date]) }}">{{ $counts[$date] ?? 0 }}</a>
                                @endif
                            </td>
                            <td><span class="badge badge-muted">v{{ $publication->pdf_version }}</span></td>
                            <td>
                                @if ($publication->other_file)
                                    <a href="{{ \App\Support\Media::url($publication->other_file) }}" target="_blank" class="text-primary-600 text-xs font-bold">ملف مرفوع</a>
                                @elseif (Route::has('publication.pdf'))
                                    <a href="{{ route('publication.pdf', $publication) }}" target="_blank" class="text-primary-600 text-xs font-bold">عرض</a>
                                @endif
                            </td>
                            <td><x-admin.status permission="publications" :active="$publication->is_active" :toggle="route('admin.publications.toggle', $publication)" /></td>
                            <td>
                                <x-admin.actions permission="publications" :edit="route('admin.publications.edit', $publication)" :destroy="route('admin.publications.destroy', $publication)"
                                                 :confirm="'حذف نشرة '.$date.'؟ أخبار اليوم لا تُحذف.'">
                                    @if ($date)
                                        <a href="{{ route('admin.news.order', ['date' => $date]) }}" class="btn btn-icon btn-sm btn-ghost" title="ترتيب أخبار النشرة"><x-admin.icon name="sort" class="w-4 h-4" /></a>
                                    @endif
                                </x-admin.actions>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-10">لا توجد نشرات.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($publications->hasPages())
            <div class="card-footer flex justify-end">{{ $publications->links('admin.partials.pagination') }}</div>
        @endif
    </div>
</x-admin.layout>
