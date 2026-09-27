@php($editing = $category->exists)
<x-admin.layout :title="$editing ? 'تعديل قسم' : 'قسم جديد'" :breadcrumb="['الأقسام' => route('admin.categories.index'), ($editing ? $category->name : 'قسم جديد') => null]">
    <x-admin.page-header :title="$editing ? 'تعديل: '.$category->name : 'قسم جديد'" :back="route('admin.categories.index')" back-label="عودة للأقسام" />
    <x-admin.errors />

    <form method="POST" action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="card max-w-2xl">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="card-body space-y-5">
            <x-admin.input name="name" label="اسم القسم" :value="$category->name" required />
            <x-admin.textarea name="description" label="الوصف" :value="$category->description" />
            <x-admin.switch name="is_active" label="ظاهر في الموقع ولوحة الإضافة" :checked="$category->is_active" />
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="save" class="w-4 h-4" /> حفظ</button></div>
    </form>
</x-admin.layout>
