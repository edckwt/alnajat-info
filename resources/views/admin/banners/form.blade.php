@php($editing = $banner->exists)
<x-admin.layout :title="$editing ? 'تعديل بانر' : 'بانر جديد'" :breadcrumb="['البانرات' => route('admin.banners.index'), ($editing ? $banner->title : 'بانر جديد') => null]">
    <x-admin.page-header :title="$editing ? 'تعديل: '.$banner->title : 'بانر جديد'" :back="route('admin.banners.index')" back-label="عودة للبانرات" />
    <x-admin.errors />

    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" class="grid grid-cols-12 gap-6 items-start">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="col-span-12 lg:col-span-8 card">
            <div class="card-body space-y-5">
                <x-admin.input name="title" label="العنوان" :value="$banner->title" required />
                <x-admin.input name="url" label="الرابط عند الضغط" :value="$banner->url" dir="ltr" icon="link" />
                <x-admin.textarea name="description" label="نص أسفل الصورة" :value="$banner->description" />
                <x-admin.textarea name="body" label="كود HTML إضافي" :value="$banner->body" rows="5" dir="ltr" hint="يُطبع كما هو أسفل البانر." />
                <x-admin.switch name="is_active" label="فعّال" :checked="$banner->is_active" />
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="save" class="w-4 h-4" /> حفظ</button></div>
        </div>
        <aside class="col-span-12 lg:col-span-4 card">
            <div class="card-header"><h2 class="card-title">صورة البانر</h2></div>
            <div class="card-body">
                <x-admin.dropzone name="image_file" :current="\App\Support\Media::url($banner->image)" label="اسحب صورة البانر هنا" />
                @error('image_file')<p class="form-error-text">{{ $message }}</p>@enderror
            </div>
        </aside>
    </form>
</x-admin.layout>
