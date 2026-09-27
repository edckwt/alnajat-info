@php
    use App\Support\ArabicDate;
    $joined = $user->created_at ? ArabicDate::monthYear($user->created_at->format('Y-m')) : null;
    // تبويب فيه أخطاء تحقق يُفتح تلقائياً بعد إعادة التوجيه
    if ($errors->hasAny(['current_password', 'password'])) $tab = 'security';
    elseif ($errors->any()) $tab = 'account';
@endphp
<x-admin.layout title="الملف الشخصي" :breadcrumb="['الملف الشخصي' => null]">

    {{-- ===================== بطاقة الغلاف ===================== --}}
    <section class="card overflow-hidden">
        <div class="h-36 sm:h-44 bg-gradient-primary relative">
            <svg class="absolute inset-0 w-full h-full opacity-20" viewBox="0 0 800 200" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 140 C 180 90 300 170 460 120 S 700 60 800 110 L800 200 L0 200Z" fill="#fff" opacity=".18"/>
                <path d="M0 170 C 200 130 320 195 520 150 S 720 110 800 150 L800 200 L0 200Z" fill="#fff" opacity=".12"/>
            </svg>
        </div>

        <div class="px-5 sm:px-8 pb-6">
            <div class="flex flex-col sm:flex-row sm:items-end gap-5 -mt-14">
                <span class="relative shrink-0 w-fit">
                    <x-admin.avatar :user="$user" class="!w-28 !h-28 !text-3xl ring-4 ring-surface shadow-card" data-profile-avatar />
                    <button type="button" class="absolute bottom-1 end-1 w-9 h-9 rounded-full bg-surface text-ink shadow-card grid place-items-center hover:text-primary-600 transition"
                            data-tab-open="#tab-account" data-focus="#avatar-zone" title="تغيير الصورة" aria-label="تغيير الصورة">
                        <x-admin.icon name="camera" class="w-4 h-4" />
                    </button>
                </span>

                <div class="flex-1 min-w-0 sm:pb-2">
                    <h1 class="text-2xl font-extrabold">{{ $user->name }}</h1>
                    <p class="text-sm text-muted mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                        <span class="flex items-center gap-1.5"><x-admin.icon name="briefcase" class="w-4 h-4" /> {{ $user->roleName() }}</span>
                        @if ($user->username)<span class="flex items-center gap-1.5" dir="ltr"><x-admin.icon name="user" class="w-4 h-4" /> {{ '@'.$user->username }}</span>@endif
                        @if ($joined)<span class="flex items-center gap-1.5"><x-admin.icon name="calendar" class="w-4 h-4" /> عضو منذ {{ $joined }}</span>@endif
                    </p>
                </div>

                <div class="flex gap-2 sm:pb-2">
                    <button type="button" class="btn btn-primary btn-sm gap-1.5" data-tab-open="#tab-account"><x-admin.icon name="edit" class="w-4 h-4" /> تعديل البيانات</button>
                    <button type="button" class="btn btn-secondary btn-sm gap-1.5" data-tab-open="#tab-security"><x-admin.icon name="lock" class="w-4 h-4" /> كلمة المرور</button>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-7 pt-6 border-t border-line/70">
                <div><p class="text-xl font-extrabold">{{ number_format($stats['news']) }}</p><p class="text-xs text-muted mt-1">خبر أضفته</p></div>
                <div><p class="text-xl font-extrabold">{{ number_format($stats['month']) }}</p><p class="text-xs text-muted mt-1">هذا الشهر</p></div>
                <div><p class="text-xl font-extrabold text-success-600">{{ number_format($stats['published']) }}</p><p class="text-xs text-muted mt-1">منشور</p></div>
                <div><p class="text-sm font-extrabold mt-1">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</p><p class="text-xs text-muted mt-1">آخر دخول</p></div>
            </div>
        </div>
    </section>

    {{-- ===================== التبويبات ===================== --}}
    <div data-tabs class="space-y-6 mt-6">
        <div class="card !p-1.5 !rounded-full w-fit max-w-full overflow-x-auto no-scrollbar">
            <div class="tabs-pill !bg-transparent">
                @foreach ($tabs as $key => $label)
                    <button type="button" class="tab" data-tab="#tab-{{ $key }}" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- ---------------- نظرة عامة ---------------- --}}
        <div id="tab-overview" data-tab-panel @if ($tab !== 'overview') hidden @endif class="grid grid-cols-12 gap-6 items-start">
            <section class="col-span-12 lg:col-span-4 space-y-6">
                <div class="card">
                    <div class="card-header"><h2 class="card-title">نبذة</h2></div>
                    <div class="card-body">
                        <p class="text-sm text-muted leading-relaxed">{{ $user->bio ?: 'لم تكتب نبذة بعد.' }}</p>
                        <ul class="mt-5 space-y-3.5 text-sm">
                            @foreach ([
                                ['mail', 'البريد', $user->email, 'bg-primary-500/10 text-primary-600', 'ltr'],
                                ['phone', 'الجوال', $user->phone, 'bg-success-500/10 text-success-600', 'ltr'],
                                ['user', 'اسم المستخدم', $user->username, 'bg-warning-500/10 text-warning-600', 'ltr'],
                            ] as [$icon, $label, $value, $tone, $dir])
                                <li class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-xl {{ $tone }} grid place-items-center shrink-0"><x-admin.icon :name="$icon" class="w-4 h-4" /></span>
                                    <span class="min-w-0"><span class="block text-[11px] text-faint">{{ $label }}</span>
                                        <span class="block truncate font-semibold" @if ($value) dir="{{ $dir }}" @endif>{{ $value ?: '—' }}</span></span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                @php
                    $checks = ['الاسم والبريد' => filled($user->name) && filled($user->email), 'الصورة الشخصية' => filled($user->avatar), 'رقم الجوال' => filled($user->phone), 'النبذة' => filled($user->bio)];
                    $percent = (int) round(count(array_filter($checks)) / count($checks) * 100);
                @endphp
                <div class="card">
                    <div class="card-header"><h2 class="card-title">اكتمال الملف</h2><span class="badge badge-primary">{{ $percent }}%</span></div>
                    <div class="card-body space-y-4">
                        <div class="progress"><div class="progress-bar" style="width: {{ $percent }}%"></div></div>
                        <ul class="text-xs space-y-2.5">
                            @foreach ($checks as $label => $done)
                                <li class="flex items-center gap-2 {{ $done ? 'text-success-600' : 'text-muted' }}">
                                    @if ($done)<x-admin.icon name="check" class="w-4 h-4" />@else<span class="w-4 h-4 rounded-full border-2 border-current"></span>@endif
                                    {{ $label }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>

            <section class="col-span-12 lg:col-span-8">
                <div class="card">
                    <div class="card-header">
                        <div><h2 class="card-title">آخر أخبارك</h2><p class="card-subtitle">الأخبار التي أضفتها بنفسك</p></div>
                        @can('news.view')<a href="{{ route('admin.news.index') }}" class="text-xs font-bold text-primary-600 hover:underline">كل الأخبار</a>@endcan
                    </div>
                    @if ($recentNews->isEmpty())
                        <div class="card-body text-sm text-muted text-center py-10">لم تضف أخباراً بعد.</div>
                    @else
                        <div class="table-wrap">
                            <table class="table table-hover">
                                <thead><tr class="border-b border-line"><th>العنوان</th><th>التاريخ</th><th>الحالة</th></tr></thead>
                                <tbody>
                                    @foreach ($recentNews as $item)
                                        <tr>
                                            <td class="font-semibold">
                                                @can('news.update', $item)
                                                    <a href="{{ route('admin.news.edit', $item) }}" class="hover:text-primary-600">{{ \Illuminate\Support\Str::limit($item->title, 70) }}</a>
                                                @else
                                                    {{ \Illuminate\Support\Str::limit($item->title, 70) }}
                                                @endcan
                                            </td>
                                            <td class="text-muted text-xs whitespace-nowrap">{{ $item->published_date?->toDateString() ?? $item->created_at?->toDateString() }}</td>
                                            <td><x-admin.status :active="$item->is_active" on="منشور" off="مخفي" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        {{-- ---------------- البيانات الشخصية ---------------- --}}
        <div id="tab-account" data-tab-panel @if ($tab !== 'account') hidden @endif>
            <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="grid grid-cols-12 gap-6 items-start">
                @csrf @method('PUT')
                <section class="col-span-12 lg:col-span-8">
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">البيانات الشخصية</h2></div>
                        <div class="card-body grid sm:grid-cols-2 gap-5">
                            <div class="sm:col-span-2"><x-admin.input name="name" label="الاسم" :value="$user->name" required autocomplete="name" /></div>
                            <x-admin.input name="username" label="اسم المستخدم" :value="$user->username" dir="ltr" icon="user" autocomplete="username" hint="للدخول إلى اللوحة، بالأحرف الإنجليزية." />
                            <x-admin.input name="email" type="email" label="البريد الإلكتروني" :value="$user->email" dir="ltr" icon="mail" autocomplete="email" hint="يمكن الدخول به أيضاً." />
                            <x-admin.input name="phone" type="tel" label="الجوال" :value="$user->phone" dir="ltr" icon="phone" autocomplete="tel" />
                            <div class="hidden sm:block"></div>
                            <div class="sm:col-span-2"><x-admin.textarea name="bio" label="نبذة" :value="$user->bio" rows="3" hint="تظهر في ملفك الشخصي فقط، حتى 500 حرف." maxlength="500" /></div>
                        </div>
                        <div class="card-footer flex justify-end gap-2">
                            <a href="{{ route('admin.profile.show', ['tab' => 'account']) }}" class="btn btn-secondary">إلغاء</a>
                            <button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="save" class="w-4 h-4" /> حفظ التغييرات</button>
                        </div>
                    </div>
                </section>

                <section class="col-span-12 lg:col-span-4">
                    <div class="card" id="avatar-zone">
                        <div class="card-header"><h2 class="card-title">الصورة الشخصية</h2></div>
                        <div class="card-body space-y-4">
                            <div class="flex items-center gap-4">
                                <x-admin.avatar :user="$user" class="avatar-xl" data-profile-avatar />
                                <p class="text-xs text-muted leading-relaxed">صورة مربعة يُفضَّل ٤٠٠×٤٠٠ بكسل، PNG أو JPG أو WebP، حتى ٢ ميجابايت. تظهر في الترويسة وقائمة الأعضاء.</p>
                            </div>
                            <x-admin.dropzone name="avatar_file" accept="image/png,image/jpeg,image/webp,image/gif" :max-mb="2" aspect="1" :max-width="800"
                                              label="اسحب صورتك وأفلتها هنا" hint="أو اضغط للاختيار، أو الصق صورة" data-avatar-dropzone />
                            @error('avatar_file')<p class="form-error-text">{{ $message }}</p>@enderror
                            @if ($user->avatar)
                                <label class="form-check text-sm">
                                    <input type="checkbox" name="remove_avatar" value="1" class="form-checkbox" data-remove-avatar>
                                    <span>إزالة الصورة الحالية والعودة للحروف الأولى</span>
                                </label>
                            @endif
                        </div>
                    </div>
                </section>
            </form>
        </div>

        {{-- ---------------- الأمان ---------------- --}}
        <div id="tab-security" data-tab-panel @if ($tab !== 'security') hidden @endif class="grid grid-cols-12 gap-6 items-start">
            <section class="col-span-12 lg:col-span-7">
                <form method="POST" action="{{ route('admin.profile.password') }}" class="card" data-password-form>
                    @csrf @method('PUT')
                    <div class="card-header">
                        <div><h2 class="card-title">تغيير كلمة المرور</h2><p class="card-subtitle">بعد التغيير تخرج جلساتك على الأجهزة الأخرى.</p></div>
                    </div>
                    <div class="card-body space-y-5">
                        @foreach ([
                            ['current_password', 'كلمة المرور الحالية', '••••••••', 'current-password'],
                            ['password', 'كلمة المرور الجديدة', '٨ أحرف على الأقل، فيها حرف ورقم', 'new-password'],
                            ['password_confirmation', 'تأكيد كلمة المرور الجديدة', 'أعد كتابتها', 'new-password'],
                        ] as [$field, $label, $placeholder, $autocomplete])
                            <div>
                                <label class="form-label" for="f-{{ $field }}">{{ $label }}</label>
                                <div class="password-field">
                                    <input id="f-{{ $field }}" name="{{ $field }}" type="password" placeholder="{{ $placeholder }}" autocomplete="{{ $autocomplete }}" required
                                           @class(['form-input', 'is-invalid' => $errors->has($field)]) dir="ltr" @if ($field === 'password') minlength="8" data-strength-input @endif>
                                    <button type="button" class="password-eye" data-password-toggle aria-label="إظهار كلمة المرور" aria-pressed="false">
                                        <x-admin.icon name="eye" class="eye-on w-5 h-5" /><x-admin.icon name="eyeOff" class="eye-off w-5 h-5" />
                                    </button>
                                </div>
                                @if ($field === 'password')
                                    <div class="progress mt-3 !h-1.5"><div class="progress-bar transition-all" style="width: 0%" data-strength-bar></div></div>
                                    <p class="form-hint">قوة كلمة المرور: <span class="font-bold" data-strength-label>—</span></p>
                                @endif
                                @error($field)<p class="form-error-text">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer flex justify-end">
                        <button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="lock" class="w-4 h-4" /> تحديث كلمة المرور</button>
                    </div>
                </form>
            </section>

            <section class="col-span-12 lg:col-span-5 space-y-6">
                @if ($sessions !== null)
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">الجلسات النشطة</h2><span class="badge badge-muted">{{ count($sessions) }}</span></div>
                        <div class="card-body space-y-4">
                            @foreach ($sessions as $session)
                                <div class="flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-xl {{ $session['current'] ? 'bg-success-500/12 text-success-600' : 'bg-surface-2 text-muted' }} grid place-items-center shrink-0">
                                        <x-admin.icon name="monitor" class="w-5 h-5" />
                                    </span>
                                    <span class="flex-1 min-w-0">
                                        <span class="block text-sm font-bold">{{ $session['device'] }}</span>
                                        <span class="block text-xs text-muted"><span dir="ltr">{{ $session['ip'] }}</span> · {{ $session['last']->diffForHumans() }}</span>
                                    </span>
                                    @if ($session['current'])<span class="badge badge-success">هذه الجلسة</span>@endif
                                </div>
                            @endforeach
                        </div>
                        @if (count(array_filter($sessions, fn ($s) => ! $s['current'])))
                            <form method="POST" action="{{ route('admin.profile.sessions.destroy') }}" class="card-footer space-y-3">
                                @csrf @method('DELETE')
                                <label class="form-label" for="f-logout-password">لإنهاء الجلسات الأخرى اكتب كلمة المرور</label>
                                <div class="flex gap-2">
                                    <input id="f-logout-password" name="current_password" type="password" class="form-input" dir="ltr" autocomplete="current-password" required>
                                    <button type="submit" class="btn btn-soft-danger shrink-0">تسجيل الخروج منها</button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif

                <div class="card">
                    <div class="card-header"><h2 class="card-title">نصائح</h2></div>
                    <div class="card-body">
                        <ul class="text-sm text-muted space-y-2.5 leading-relaxed list-disc ps-5">
                            <li>استخدم كلمة مرور لا تستخدمها في موقع آخر.</li>
                            <li>اجعلها ١٢ حرفاً أو أكثر تجمع الحروف والأرقام والرموز.</li>
                            <li>إن نسيتها، يستطيع المدير تعيين كلمة جديدة لك من صفحة الأعضاء.</li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                // أزرار الغلاف تفتح تبويبها
                document.querySelectorAll('[data-tab-open]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var tab = document.querySelector('[data-tab="' + btn.getAttribute('data-tab-open') + '"]');
                        if (tab) tab.click();
                        var focus = btn.getAttribute('data-focus');
                        var target = focus ? document.querySelector(focus) : document.querySelector(btn.getAttribute('data-tab-open'));
                        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                });

                // معاينة الصورة الجديدة في الغلاف قبل الحفظ
                var zone = document.querySelector('[data-avatar-dropzone]');
                if (zone) zone.addEventListener('dz:change', function (e) {
                    var file = e.detail && e.detail.files && e.detail.files[0];
                    document.querySelectorAll('[data-profile-avatar]').forEach(function (avatar) {
                        if (!avatar.dataset.original) avatar.dataset.original = avatar.innerHTML;
                        if (file) {
                            avatar.classList.remove('bg-primary-600', 'bg-success-600', 'bg-warning-600', 'bg-danger-600', 'bg-info-600', 'text-white');
                            avatar.innerHTML = '';
                            var img = document.createElement('img'); img.src = URL.createObjectURL(file); img.alt = '';
                            avatar.appendChild(img);
                        } else {
                            avatar.innerHTML = avatar.dataset.original;
                        }
                    });
                    var remove = document.querySelector('[data-remove-avatar]');
                    if (remove && file) remove.checked = false;
                });

                // قوة كلمة المرور
                var input = document.querySelector('[data-strength-input]');
                var bar = document.querySelector('[data-strength-bar]');
                var label = document.querySelector('[data-strength-label]');
                if (input && bar && label) input.addEventListener('input', function () {
                    var v = input.value, score = 0;
                    if (v.length >= 8) score++;
                    if (v.length >= 12) score++;
                    if (/[A-Za-z؀-ۿ]/.test(v) && /\d/.test(v)) score++;
                    if (/[^A-Za-z0-9؀-ۿ]/.test(v)) score++;
                    var levels = [['—', 0, ''], ['ضعيفة', 25, '!bg-danger-500'], ['مقبولة', 50, '!bg-warning-500'], ['جيدة', 75, '!bg-success-500'], ['قوية', 100, '!bg-success-600']];
                    var level = v ? levels[Math.max(1, score)] : levels[0];
                    bar.style.width = level[1] + '%';
                    bar.className = 'progress-bar transition-all ' + level[2];
                    label.textContent = level[0];
                });
            })();
        </script>
    @endpush
</x-admin.layout>
