<x-admin.layout title="الملفات" :breadcrumb="['الملفات' => null]">
    <x-admin.page-header title="الملفات" subtitle="ارفع ملفات وانسخ روابطها لاستخدامها داخل الأخبار." />
    <x-admin.errors />

    @can('uploads.create')
    <form method="POST" action="{{ route('admin.uploads.store') }}" enctype="multipart/form-data" class="card">
        @csrf
        <div class="card-body space-y-4">
            <x-admin.dropzone name="files" :multiple="true" :accept="$accept" :max-mb="50"
                              label="اسحب الملفات وأفلتها هنا" hint="صور، PDF، Word، صوت أو فيديو — عدة ملفات معاً" />
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="upload" class="w-4 h-4" /> رفع الملفات</button></div>
    </form>
    @endcan

    <div class="card">
        <div class="table-wrap">
            <table class="table table-hover">
                <thead><tr class="border-b border-line"><th class="w-16"></th><th>الملف</th><th>الرابط</th><th>الحجم</th><th>رفعه</th><th>التاريخ</th><th class="text-center">حذف</th></tr></thead>
                <tbody>
                    @forelse ($uploads as $upload)
                        @php($url = \App\Support\Media::url($upload->path))
                        <tr>
                            <td>
                                @if (\App\Support\Media::isImage($upload->path))
                                    <img src="{{ $url }}" alt="" class="w-12 h-12 rounded-lg object-cover" loading="lazy">
                                @else
                                    <span class="w-12 h-12 rounded-lg bg-surface-2 grid place-items-center text-xs font-bold text-muted">{{ strtoupper($upload->extension) }}</span>
                                @endif
                            </td>
                            <td class="font-semibold">{{ $upload->title }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <input class="form-input form-input-sm w-72" dir="ltr" readonly value="{{ $url }}" onclick="this.select()">
                                    <button type="button" class="btn btn-icon btn-sm btn-ghost" title="نسخ" data-copy="{{ $url }}"><x-admin.icon name="copy" class="w-4 h-4" /></button>
                                </div>
                            </td>
                            <td class="text-muted text-sm whitespace-nowrap">{{ number_format($upload->size / 1024, 0) }} KB</td>
                            <td class="text-muted text-sm">{{ $upload->user?->name }}</td>
                            <td class="text-muted text-sm whitespace-nowrap">{{ $upload->created_at?->format('Y-m-d') }}</td>
                            <td><x-admin.actions permission="uploads" :destroy="route('admin.uploads.destroy', $upload)" :confirm="'حذف الملف «'.$upload->title.'» نهائياً؟ أي رابط له داخل الأخبار سيتوقف.'" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-10">لا توجد ملفات.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($uploads->hasPages())
            <div class="card-footer flex justify-end">{{ $uploads->links('admin.partials.pagination') }}</div>
        @endif
    </div>
</x-admin.layout>
