@php($editing = $newspaper->exists)
<x-admin.layout :title="$editing ? 'تعديل صحيفة' : 'صحيفة جديدة'" :breadcrumb="['الصحف' => route('admin.newspapers.index'), ($editing ? $newspaper->name : 'صحيفة جديدة') => null]">
    <x-admin.page-header :title="$editing ? 'تعديل: '.$newspaper->name : 'صحيفة جديدة'" :back="route('admin.newspapers.index')" back-label="عودة للصحف" />
    <x-admin.errors />

    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.newspapers.update', $newspaper) : route('admin.newspapers.store') }}" class="grid grid-cols-12 gap-6 items-start">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="col-span-12 lg:col-span-8 card">
            <div class="card-body space-y-5">
                <x-admin.input name="name" label="اسم الصحيفة" :value="$newspaper->name" required />
                <x-admin.select name="type" label="النوع" :options="$types" :value="$newspaper->type" />
                <x-admin.input name="url" label="الرابط" :value="$newspaper->url" dir="ltr" icon="link" />
                <x-admin.input name="country" label="الدولة (رمز)" :value="$newspaper->country" dir="ltr" hint="مثال: kw" />
                <x-admin.textarea name="description" label="الوصف" :value="$newspaper->description" />
                <x-admin.switch name="is_active" label="ظاهرة" :checked="$newspaper->is_active" />
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="save" class="w-4 h-4" /> حفظ</button></div>
        </div>
        <aside class="col-span-12 lg:col-span-4 card">
            <div class="card-header"><h2 class="card-title">الشعار</h2></div>
            <div class="card-body">
                <x-admin.dropzone name="logo_file" :current="\App\Support\Media::url($newspaper->logo)" label="اسحب شعار الصحيفة هنا" />
                @error('logo_file')<p class="form-error-text">{{ $message }}</p>@enderror
            </div>
        </aside>
    </form>
</x-admin.layout>
