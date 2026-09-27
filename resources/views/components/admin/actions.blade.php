{{-- أزرار التعديل والحذف؛ مع permission تظهر فقط لمن يملك «permission.update» و«permission.delete». --}}
@props(['edit' => null, 'destroy' => null, 'confirm' => 'حذف هذا العنصر نهائياً؟', 'permission' => null, 'record' => null])
@php
    if ($permission) {
        $args = $record ? [$record] : [];
        $edit = $edit && \Illuminate\Support\Facades\Gate::allows("$permission.update", $args) ? $edit : null;
        $destroy = $destroy && \Illuminate\Support\Facades\Gate::allows("$permission.delete", $args) ? $destroy : null;
    }
@endphp
<div class="flex items-center justify-center gap-1">
    {{ $slot }}
    @if ($edit)
        <a href="{{ $edit }}" class="btn btn-icon btn-sm btn-ghost" title="تعديل"><x-admin.icon name="edit" class="w-4 h-4" /></a>
    @endif
    @if ($destroy)
        <form method="POST" action="{{ $destroy }}" data-confirm="{{ $confirm }}">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-icon btn-sm btn-ghost text-danger-600" title="حذف"><x-admin.icon name="trash" class="w-4 h-4" /></button>
        </form>
    @endif
</div>
