<x-admin.layout title="الأقسام" :breadcrumb="['الأقسام' => null]">
    <x-admin.page-header title="الأقسام" subtitle="أقسام الأخبار. كل نوع خبر مرتبط بأقسام محددة في config/alnajat.php.">
        @can('categories.create')
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary gap-2"><x-admin.icon name="plus" class="w-4 h-4" /> قسم جديد</a>
        @endcan
    </x-admin.page-header>

    <div class="card">
        <div class="table-wrap">
            <table class="table table-hover">
                <thead><tr class="border-b border-line"><th>#</th><th>الاسم</th><th>الوصف</th><th>الأخبار</th><th>الحالة</th><th class="text-center">إجراءات</th></tr></thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td class="text-muted">{{ $category->id }}</td>
                            <td class="font-semibold">{{ $category->name }}</td>
                            <td class="text-muted text-sm">{{ \Illuminate\Support\Str::limit($category->description, 80) }}</td>
                            <td><a class="text-primary-600 hover:underline" href="{{ route('admin.news.index', ['category' => $category->id]) }}">{{ number_format($category->news_count) }}</a></td>
                            <td><x-admin.status permission="categories" :active="$category->is_active" :toggle="route('admin.categories.toggle', $category)" on="ظاهر" /></td>
                            <td><x-admin.actions permission="categories" :edit="route('admin.categories.edit', $category)" :destroy="route('admin.categories.destroy', $category)"
                                                 :confirm="'حذف قسم «'.$category->name.'»؟ الأخبار تبقى لكن تُزال من هذا القسم.'" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-10">لا توجد أقسام.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin.layout>
