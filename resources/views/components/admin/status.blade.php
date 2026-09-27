{{-- حالة (منشور/مخفي). مع toggle يصبح زراً للتبديل، إن سمحت صلاحية «permission.publish». --}}
@props(['active', 'toggle' => null, 'on' => 'منشور', 'off' => 'مخفي', 'permission' => null, 'record' => null])
@php
    if ($toggle && $permission) {
        $toggle = \Illuminate\Support\Facades\Gate::allows("$permission.publish", $record ? [$record] : []) ? $toggle : null;
    }
@endphp
@if ($toggle)
    <form method="POST" action="{{ $toggle }}">
        @csrf @method('PATCH')
        <button type="submit" title="تبديل الحالة" @class(['badge', 'badge-success' => $active, 'badge-danger' => ! $active])>{{ $active ? $on : $off }}</button>
    </form>
@else
    <span @class(['badge', 'badge-success' => $active, 'badge-danger' => ! $active])>{{ $active ? $on : $off }}</span>
@endif
