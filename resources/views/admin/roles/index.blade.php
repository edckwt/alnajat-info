<x-admin.layout title="الأدوار والصلاحيات" :breadcrumb="['الأدوار والصلاحيات' => null]">
    <x-admin.page-header title="الأدوار والصلاحيات" subtitle="الدور مجموعة صلاحيات تُسند للأعضاء، ويمكن إضافة صلاحيات لعضو بعينه من صفحة تعديله.">
        @can('roles.create')
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary gap-2"><x-admin.icon name="plus" class="w-4 h-4" /> دور جديد</a>
        @endcan
    </x-admin.page-header>
    <x-admin.errors />

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($roles as $role)
            @php($count = $role->isAdmin() ? $total : count($role->permissions ?? []))
            <div class="card">
                <div class="card-body space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-extrabold text-lg">{{ $role->name }}</p>
                            <p class="text-sm text-muted mt-1">{{ $role->description ?: '—' }}</p>
                        </div>
                        @if ($role->is_system)<span class="badge badge-muted shrink-0">أساسي</span>@endif
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="badge badge-primary">{{ $count }} من {{ $total }} صلاحية</span>
                        <span class="text-muted">{{ $role->users_count }} عضو</span>
                    </div>
                    <div class="progress"><div class="progress-bar" style="width: {{ $total ? round($count / $total * 100) : 0 }}%"></div></div>
                </div>
                <div class="card-footer flex items-center justify-between">
                    @if ($role->isAdmin())
                        <span class="text-xs text-muted">كل الصلاحيات دائماً</span>
                    @else
                        <span></span>
                        <x-admin.actions :edit="auth()->user()->can('roles.update') ? route('admin.roles.edit', $role) : null"
                                         :destroy="! $role->is_system && auth()->user()->can('roles.delete') ? route('admin.roles.destroy', $role) : null"
                                         :confirm="'حذف الدور «'.$role->name.'»؟'" />
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-admin.layout>
