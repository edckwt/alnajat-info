@php($editing = $role->exists)
<x-admin.layout :title="$editing ? 'تعديل دور' : 'دور جديد'" :breadcrumb="['الأدوار والصلاحيات' => route('admin.roles.index'), ($editing ? $role->name : 'دور جديد') => null]">
    <x-admin.page-header :title="$editing ? 'تعديل: '.$role->name : 'دور جديد'" :back="route('admin.roles.index')" back-label="عودة للأدوار" />
    <x-admin.errors />

    <form method="POST" action="{{ $editing ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="space-y-6">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="card">
            <div class="card-body grid grid-cols-12 gap-5">
                <div class="col-span-12 md:col-span-5"><x-admin.input name="name" label="اسم الدور" :value="$role->name" required /></div>
                <div class="col-span-12 md:col-span-7"><x-admin.input name="description" label="الوصف" :value="$role->description" /></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">الصلاحيات</h2>
                    <p class="card-subtitle mt-1">حدد ما يستطيعه أعضاء هذا الدور في كل قسم من اللوحة.</p>
                </div>
            </div>
            <div class="card-body">
                <x-admin.permission-matrix :selected="$role->permissions ?? []" :grantable="$grantable" />
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="save" class="w-4 h-4" /> حفظ الدور</button></div>
        </div>
    </form>
</x-admin.layout>
