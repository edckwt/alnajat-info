@php
    $editing = $user->exists;
    $self = $editing && auth()->user()->is($user);
    $currentRole = old('role', $user->role);
    $inherited = $rolePermissions[$currentRole] ?? [];
@endphp
<x-admin.layout :title="$editing ? 'تعديل عضو' : 'عضو جديد'" :breadcrumb="['الأعضاء' => route('admin.users.index'), ($editing ? $user->name : 'عضو جديد') => null]">
    <x-admin.page-header :title="$editing ? 'تعديل: '.$user->name : 'عضو جديد'" :back="route('admin.users.index')" back-label="عودة للأعضاء" />
    <x-admin.errors />

    <form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="grid grid-cols-12 gap-6 items-start">
            <div class="col-span-12 lg:col-span-7 card">
                <div class="card-header"><h2 class="card-title">بيانات العضو</h2></div>
                <div class="card-body grid grid-cols-12 gap-5">
                    <div class="col-span-12 md:col-span-6"><x-admin.input name="name" label="الاسم" :value="$user->name" required /></div>
                    <div class="col-span-12 md:col-span-6"><x-admin.input name="username" label="اسم المستخدم" :value="$user->username" dir="ltr" required hint="حروف إنجليزية وأرقام و - _" /></div>
                    <div class="col-span-12 md:col-span-7"><x-admin.input name="email" label="البريد الإلكتروني" type="email" :value="$user->email" dir="ltr" /></div>
                    <div class="col-span-12 md:col-span-5"><x-admin.input name="phone" label="الجوال" type="tel" :value="$user->phone" dir="ltr" /></div>
                    <div class="col-span-12 md:col-span-6"><x-admin.input name="password" label="كلمة المرور" type="password" autocomplete="new-password"
                                   :hint="$editing ? 'اتركها فارغة للإبقاء على الحالية. 8 أحرف على الأقل.' : '8 أحرف على الأقل.'" :required="! $editing" /></div>
                    <div class="col-span-12 md:col-span-6"><x-admin.input name="password_confirmation" label="تأكيد كلمة المرور" type="password" autocomplete="new-password" /></div>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-5 space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">الصورة الشخصية</h2></div>
                <div class="card-body space-y-4">
                    @if ($editing)
                        <div class="flex items-center gap-4">
                            <x-admin.avatar :user="$user" class="avatar-lg" />
                            <p class="text-xs text-muted leading-relaxed">صورة مربعة يُفضَّل ٤٠٠×٤٠٠ بكسل، حتى ٢ ميجابايت.</p>
                        </div>
                    @endif
                    <x-admin.dropzone name="avatar_file" accept="image/png,image/jpeg,image/webp,image/gif" :max-mb="2" aspect="1" :max-width="800" label="اسحب صورة العضو وأفلتها هنا" />
                    @if ($editing && $user->avatar)
                        <label class="form-check text-sm"><input type="checkbox" name="remove_avatar" value="1" class="form-checkbox"><span>إزالة الصورة الحالية</span></label>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">الدور والحالة</h2></div>
                <div class="card-body space-y-5">
                    @if ($self)
                        <input type="hidden" name="role" value="{{ $user->role }}">
                        <p class="text-sm">دورك: <strong>{{ $roles[$user->role] ?? $user->role }}</strong></p>
                        <p class="text-sm text-muted">لا يمكنك تغيير دورك أو صلاحياتك أو إيقاف حسابك بنفسك.</p>
                    @else
                        <x-admin.select name="role" label="الدور" :options="$roles" :value="$currentRole" data-role-select
                                        hint="صلاحيات الدور تنطبق تلقائياً، ويمكن إضافة غيرها أدناه." />
                        <x-admin.switch name="is_active" label="الحساب فعّال" :checked="$user->is_active" />
                    @endif
                    @can('roles.view')
                        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-primary-600 hover:underline"><x-admin.icon name="users" class="w-4 h-4" /> إدارة الأدوار</a>
                    @endcan
                </div>
            </div>
            </div>
        </div>

        @unless ($self)
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">صلاحيات إضافية لهذا العضو</h2>
                        <p class="card-subtitle mt-1">المعلَّم بـ «الدور» يأتي من دوره. حدد ما يحتاجه زيادة على دوره فقط.</p>
                    </div>
                </div>
                <div class="card-body" data-role-permissions='@json($rolePermissions)'>
                    <x-admin.permission-matrix :selected="$user->permissions ?? []" :inherited="$inherited" :grantable="$grantable" />
                </div>
            </div>
        @endunless

        <div class="flex justify-end"><button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="save" class="w-4 h-4" /> حفظ</button></div>
    </form>

    @unless ($self)
        @push('scripts')
            <script>
                (function () {
                    var select = document.querySelector('[data-role-select]');
                    var box = document.querySelector('[data-role-permissions]');
                    if (!select || !box) return;
                    var map = JSON.parse(box.dataset.rolePermissions || '{}');
                    var items = box.querySelectorAll('[data-permission-item]');

                    // الحالة الأصلية لكل مربع: هل هو محظور لأن العضو الحالي لا يملكه؟
                    items.forEach(function (i) {
                        var fromRole = i.closest('label').title === 'من الدور';
                        i.dataset.grantLocked = (i.disabled && !fromRole) ? '1' : '0';
                        i.dataset.userChecked = (!fromRole && i.checked) ? '1' : '0';
                    });

                    function apply() {
                        var role = map[select.value] || [];
                        items.forEach(function (i) {
                            var label = i.closest('label');
                            var badge = label.querySelector('[data-role-badge]');
                            var fromRole = role.indexOf(i.value) !== -1;
                            if (fromRole) {
                                i.checked = true; i.disabled = true; label.classList.add('opacity-60'); label.title = 'من الدور';
                                if (!badge) label.querySelector('span').insertAdjacentHTML('beforeend', ' <span data-role-badge class="text-[10px] text-primary-600 font-bold">(الدور)</span>');
                            } else {
                                i.disabled = i.dataset.grantLocked === '1';
                                i.checked = i.dataset.userChecked === '1';
                                label.classList.toggle('opacity-60', i.disabled);
                                label.title = i.disabled ? 'لا تملك هذه الصلاحية لتمنحها' : '';
                                if (badge) badge.remove();
                                label.querySelectorAll('span > span').forEach(function (s) { if (s.textContent === '(الدور)') s.remove(); });
                            }
                        });
                        box.querySelectorAll('[data-permission-module]').forEach(function (m) { m.dispatchEvent(new Event('change', { bubbles: false })); });
                    }

                    items.forEach(function (i) { i.addEventListener('change', function () { if (!i.disabled) i.dataset.userChecked = i.checked ? '1' : '0'; }); });
                    select.addEventListener('change', apply);
                })();
            </script>
        @endpush
    @endunless
</x-admin.layout>
