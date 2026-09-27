<x-admin.layout title="الأعضاء" :breadcrumb="['الأعضاء' => null]">
    <x-admin.page-header title="الأعضاء" subtitle="لكل عضو دور يحدد صلاحياته، ويمكن منحه صلاحيات إضافية.">
        @can('roles.view')
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary gap-2"><x-admin.icon name="gear" class="w-4 h-4" /> الأدوار والصلاحيات</a>
        @endcan
        @can('users.create')
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary gap-2"><x-admin.icon name="plus" class="w-4 h-4" /> عضو جديد</a>
        @endcan
    </x-admin.page-header>
    <x-admin.errors />

    <div class="card">
        <div class="table-wrap">
            <table class="table table-hover">
                <thead><tr class="border-b border-line"><th>الاسم</th><th>اسم المستخدم</th><th>البريد</th><th>الدور</th><th>آخر دخول</th><th>الحالة</th><th class="text-center">إجراءات</th></tr></thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <span class="flex items-center gap-3">
                                    <x-admin.avatar :user="$user" class="avatar-sm" />
                                    <span class="font-semibold">{{ $user->name }}</span>
                                    @if (auth()->user()->is($user))<a href="{{ route('admin.profile.show') }}" class="badge badge-primary" title="ملفك الشخصي">أنت</a>@endif
                                </span>
                            </td>
                            <td dir="ltr" class="text-end">{{ $user->username }}</td>
                            <td class="text-muted text-sm" dir="ltr">{{ $user->email }}</td>
                            <td>
                                <span class="badge badge-muted">{{ $roles[$user->role] ?? $user->role }}</span>
                                @if (count($user->permissions ?? []))<span class="badge badge-primary" title="صلاحيات إضافية">+{{ count($user->permissions) }}</span>@endif
                            </td>
                            <td class="text-muted text-sm whitespace-nowrap">
                                {{ $user->last_login_at?->format('Y-m-d H:i') ?? '—' }}
                                @if ($user->hasLegacyPassword())<span class="block text-[11px] text-warning-600">لم يدخل منذ الانتقال</span>@endif
                            </td>
                            <td><x-admin.status :active="$user->is_active" on="فعّال" off="موقوف" /></td>
                            @php($manageable = ! $user->isAdmin() || auth()->user()->isAdmin())
                            <td><x-admin.actions :edit="$manageable && auth()->user()->can('users.update') ? route('admin.users.edit', $user) : null"
                                                 :destroy="$manageable && ! auth()->user()->is($user) && auth()->user()->can('users.delete') ? route('admin.users.destroy', $user) : null"
                                                 :confirm="'حذف العضو «'.$user->name.'»؟ أخباره تبقى.'" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-admin.layout>
