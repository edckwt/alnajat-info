<x-admin.layout title="البانرات" :breadcrumb="['البانرات' => null]">
    <x-admin.page-header title="البانرات" subtitle="تُختار داخل صناديق الصفحة الرئيسية والنشرة من الإعدادات.">
        @can('banners.create')
            <a href="{{ route('admin.banners.create') }}" class="btn btn-primary gap-2"><x-admin.icon name="plus" class="w-4 h-4" /> بانر جديد</a>
        @endcan
    </x-admin.page-header>

    <div class="grid grid-cols-12 gap-6">
        @forelse ($banners as $banner)
            <div class="col-span-12 sm:col-span-6 xl:col-span-4 card overflow-hidden">
                @if ($banner->image)
                    <img src="{{ \App\Support\Media::url($banner->image) }}" alt="" class="w-full h-40 object-cover bg-surface-2">
                @endif
                <div class="card-body space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-bold">{{ $banner->title }}</h3>
                        <x-admin.status permission="banners" :active="$banner->is_active" :toggle="route('admin.banners.toggle', $banner)" on="فعّال" off="موقوف" />
                    </div>
                    @if ($banner->url)<a href="{{ $banner->url }}" target="_blank" class="block text-xs text-muted truncate" dir="ltr">{{ $banner->url }}</a>@endif
                    <p class="text-xs text-muted">{{ number_format($banner->clicks) }} نقرة</p>
                </div>
                <div class="card-footer">
                    <x-admin.actions permission="banners" :edit="route('admin.banners.edit', $banner)" :destroy="route('admin.banners.destroy', $banner)" :confirm="'حذف بانر «'.$banner->title.'»؟'" />
                </div>
            </div>
        @empty
            <div class="col-span-12 card card-body text-center text-muted py-10">لا توجد بانرات.</div>
        @endforelse
    </div>
</x-admin.layout>
