<x-admin.layout :title="__('admin.nav.firewall')" :breadcrumb="[__('admin.nav.firewall') => null]">
    <x-admin.page-header :title="__('admin.nav.firewall')"
                         subtitle="يُحظر تلقائياً من يطلب روابط لا يطلبها زائر حقيقي (/.git، /.env، wp-login.php …) أو صفحات غير موجودة كثيرة خلال دقائق. الحظر مؤقت ويطول مع التكرار: يوم، ثم أسبوع، ثم شهر." />
    <x-admin.errors />

    @if (! $enabled)
        <div class="alert alert-warning" role="status">
            <x-admin.icon name="alert" class="w-5 h-5 shrink-0" />
            <p>الحظر متوقف{{ $envDisabled ? ' من ملف ‎.env (FIREWALL_ENABLED=false)' : ' من الإعدادات أدناه' }}: لا يُحظر أحد ولا يُرفض أحد.</p>
        </div>
    @endif

    <div class="alert {{ $myIpAllowed ? 'alert-success' : 'alert-info' }}" role="status">
        <x-admin.icon name="shield" class="w-5 h-5 shrink-0" />
        <p>
            عنوانك الحالي: <code dir="ltr" class="font-mono font-bold">{{ $myIp }}</code>
            — {{ $myIpAllowed ? 'ضمن الاستثناءات، لا يُحظر أبداً.' : 'غير مستثنى. أضف عنوان المكتب إلى الاستثناءات حتى لا يُحظر بالخطأ.' }}
        </p>
    </div>

    <div class="grid grid-cols-12 gap-6">
        @foreach ([
            ['محظور الآن', $stats['active'], 'shield', 'bg-danger-500/12 text-danger-600'],
            ['حُظر أو تجدد اليوم', $stats['today'], 'clock', 'bg-warning-500/12 text-warning-600'],
            ['طلبات رُفضت (للمحظورين الآن)', $stats['hits'], 'x', 'bg-primary-500/12 text-primary-600'],
        ] as [$label, $value, $icon, $tone])
            <div class="col-span-12 sm:col-span-4">
                <div class="card card-body flex items-center gap-4">
                    <span class="stat-icon {{ $tone }} shrink-0"><x-admin.icon :name="$icon" class="w-6 h-6" /></span>
                    <div>
                        <p class="text-2xl font-extrabold tracking-tight">{{ number_format($value) }}</p>
                        <p class="text-sm text-muted">{{ $label }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-12 gap-6 items-start">
        {{-- القائمة --}}
        <section class="col-span-12 xl:col-span-8 card">
            <form method="GET" class="card-body flex flex-wrap items-center gap-3">
                <div class="seg" role="tablist" aria-label="الحالة">
                    @foreach (['active' => 'محظور الآن', 'expired' => 'انتهى حظره', 'all' => 'الكل'] as $key => $label)
                        <label><input type="radio" name="status" value="{{ $key }}" @checked($status === $key) onchange="this.form.submit()"><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                <input type="search" name="q" value="{{ request('q') }}" class="form-input flex-1 min-w-48" dir="ltr" placeholder="ابحث في IP أو الرابط…">
                <button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="search" class="w-4 h-4" /> بحث</button>
            </form>

            <div class="table-wrap">
                <table class="table table-hover">
                    <thead><tr class="border-b border-line"><th>IP</th><th>السبب</th><th class="text-center">المرات</th><th class="text-center">رُفض</th><th>حتى</th><th>آخر حظر</th><th class="text-center">إجراءات</th></tr></thead>
                    <tbody>
                        @forelse ($blocked as $row)
                            <tr>
                                <td class="whitespace-nowrap" dir="ltr">
                                    @can('missing_links.view')
                                        <a href="{{ route('admin.missing-links.index', ['ip' => $row->ip]) }}" class="font-mono font-semibold text-ink hover:text-primary-600 hover:underline" title="روابطه المفقودة">{{ $row->ip }}</a>
                                    @else
                                        <span class="font-mono font-semibold">{{ $row->ip }}</span>
                                    @endcan
                                </td>
                                <td class="max-w-xs">
                                    <span @class(['badge', 'badge-danger' => $row->reason === 'trap', 'badge-warning' => $row->reason === 'flood', 'badge-primary' => $row->reason === 'manual'])>{{ $row->reasonLabel() }}</span>
                                    @if ($row->trigger)<span class="block mt-1 text-xs text-muted break-all" dir="auto">{{ $row->trigger }}</span>@endif
                                    @if ($row->creator)<span class="block text-[11px] text-faint">بواسطة {{ $row->creator->name }}</span>@endif
                                </td>
                                <td class="text-center font-bold">{{ $row->strikes }}</td>
                                <td class="text-center text-muted">{{ number_format($row->hits) }}</td>
                                <td class="text-sm whitespace-nowrap">
                                    @if ($row->isPermanent())
                                        <span class="badge badge-solid-danger">دائم</span>
                                    @elseif ($row->isActive())
                                        <span class="font-semibold">{{ $row->blocked_until->format('Y-m-d H:i') }}</span>
                                        <span class="block text-[11px] text-muted">{{ $row->blocked_until->diffForHumans() }}</span>
                                    @else
                                        <span class="badge badge-muted">انتهى</span>
                                    @endif
                                </td>
                                <td class="text-muted text-sm whitespace-nowrap">{{ $row->updated_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        @can('firewall.delete')
                                            @if ($row->isActive())
                                                <form method="POST" action="{{ route('admin.firewall.unblock', $row) }}" data-confirm="رفع الحظر عن {{ $row->ip }}؟" data-confirm-title="رفع الحظر" data-confirm-ok="رفع الحظر" data-confirm-tone="primary"
                                                      data-confirm-note="يبقى السجل، فإن عاد خلال 90 يوماً يُحظر مدة أطول.">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-soft">رفع الحظر</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.firewall.destroy', $row) }}" data-confirm="حذف سجل {{ $row->ip }} نهائياً؟" data-confirm-note="يُرفع حظره إن كان محظوراً، ويبدأ عدّ التكرار من جديد.">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-icon btn-sm btn-ghost text-danger-600" title="حذف السجل"><x-admin.icon name="trash" class="w-4 h-4" /></button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-10">{{ $status === 'active' ? 'لا يوجد عنوان محظور الآن.' : 'لا توجد سجلات.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($blocked->hasPages())
                <div class="card-footer flex justify-end">{{ $blocked->links('admin.partials.pagination') }}</div>
            @endif
        </section>

        <aside class="col-span-12 xl:col-span-4 space-y-6">
            @can('firewall.create')
                <form method="POST" action="{{ route('admin.firewall.store') }}" class="card">
                    @csrf
                    <div class="card-header"><div><h2 class="card-title">حظر عنوان يدوياً</h2></div></div>
                    <div class="card-body space-y-4">
                        <x-admin.input name="ip" label="العنوان (IP)" :value="request('ip')" dir="ltr" placeholder="203.0.113.7" required />
                        <x-admin.select name="duration" label="المدة" :options="$durations" value="1440" />
                        <x-admin.input name="note" label="ملاحظة (اختياري)" placeholder="سبب الحظر" />
                        <button type="submit" class="btn btn-danger w-full gap-2"><x-admin.icon name="shield" class="w-4 h-4" /> حظر</button>
                    </div>
                </form>
            @endcan

            @can('firewall.settings')
                <form method="POST" action="{{ route('admin.firewall.settings') }}" class="card">
                    @csrf @method('PUT')
                    <div class="card-header"><div><h2 class="card-title">الإعدادات</h2><p class="card-subtitle">المصيدة والاستثناءات وحد الأخطاء.</p></div></div>
                    <div class="card-body space-y-5">
                        <x-admin.switch name="enabled" label="تفعيل الحظر" :checked="$enabled" />

                        <x-admin.textarea name="trap_paths" label="روابط المصيدة" :value="$trapText" rows="8" dir="ltr" class="font-mono text-xs"
                                          :hint="'نمط في كل سطر: أي رابط يحتويه يُحظر طالبه فوراً. لا يُقبل نمط يطابق رابطاً حقيقياً في الموقع. '.($trapCustom ? 'قائمة مخصصة؛ امسحها كلها للعودة إلى الافتراضية.' : 'هذه القائمة الافتراضية.')" />

                        <x-admin.textarea name="allowlist" label="استثناءات (لا تُحظر أبداً)" :value="$allowText" rows="4" dir="ltr" class="font-mono text-xs"
                                          hint="عنوان أو نطاق CIDR في كل سطر، مثل عنوان المكتب. 127.0.0.1 مستثنى دائماً." />
                        @if ($envAllow)
                            <p class="text-xs text-muted">ومستثنى من ‎.env (FIREWALL_ALLOW): <span dir="ltr" class="font-mono">{{ implode('، ', $envAllow) }}</span></p>
                        @endif

                        <div class="grid grid-cols-2 gap-3">
                            <x-admin.input name="not_found_limit" type="number" label="صفحات غير موجودة" :value="$limit" min="5" max="1000" />
                            <x-admin.input name="not_found_minutes" type="number" label="خلال (دقائق)" :value="$minutes" min="1" max="1440" />
                        </div>
                        <p class="form-hint -mt-3">لا تُحتسب الصور والملفات الثابتة، ولا محركات البحث المعروفة.</p>

                        <button type="submit" class="btn btn-primary w-full gap-2"><x-admin.icon name="save" class="w-4 h-4" /> حفظ</button>
                    </div>
                </form>
            @endcan
        </aside>
    </div>
</x-admin.layout>
