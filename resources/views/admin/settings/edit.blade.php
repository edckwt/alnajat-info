@php
    $s = fn (string $key) => $settings[$key] ?? null;
    $yesNo = ['0' => 'لا', '1' => 'نعم'];
@endphp
<x-admin.layout title="الإعدادات" :breadcrumb="['الإعدادات' => null]">
    <x-admin.page-header title="الإعدادات" />
    <x-admin.errors />

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" data-tabs class="space-y-6">
        @csrf @method('PUT')
        <input type="hidden" name="tab" value="{{ $tab }}" id="currentTab">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="tabs-pill flex-wrap">
                @foreach ($tabs as $key => $label)
                    <button type="button" class="tab" data-tab="#tab-{{ $key }}" data-tab-key="{{ $key }}" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">{{ $label }}</button>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="save" class="w-4 h-4" /> حفظ الإعدادات</button>
        </div>

        {{-- ===================== عام ===================== --}}
        @if (isset($tabs['general']))
        <div id="tab-general" data-tab-panel @if ($tab !== 'general') hidden @endif class="grid grid-cols-12 gap-6 items-start">
            <div class="col-span-12 card">
                <div class="card-header flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="card-title">واجهة الموقع</h2>
                        <p class="text-xs text-muted mt-1">الواجهة التي يراها الزوار. يمكنك معاينة أي واجهة قبل اعتمادها، والرجوع للكلاسيكية في أي وقت.</p>
                    </div>
                </div>
                <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-4" role="radiogroup" aria-label="واجهة الموقع">
                    @foreach ($themes as $key => $theme)
                        <label class="theme-option">
                            <input type="radio" name="setting[site_theme]" value="{{ $key }}" class="sr-only peer" @checked($activeTheme === $key)>
                            <span class="theme-option-card">
                                <span class="theme-shot theme-shot-{{ $key }}" aria-hidden="true">
                                    @if ($key === 'classic')
                                        <span class="ts-cover"><span></span><span></span></span>
                                        <span class="ts-title"></span>
                                        <span class="ts-img"></span>
                                        <span class="ts-line"></span><span class="ts-line short"></span>
                                    @else
                                        <span class="ts-top"></span>
                                        <span class="ts-head"><span></span><span></span></span>
                                        <span class="ts-hero"><span class="ts-hero-text"><span></span><span></span><span class="ts-btn"></span></span><span class="ts-hero-cover"></span></span>
                                        <span class="ts-cards"><span></span><span></span><span></span><span></span></span>
                                    @endif
                                </span>
                                <span class="flex items-start justify-between gap-3 p-4">
                                    <span class="min-w-0">
                                        <span class="flex items-center gap-2 font-bold">
                                            {{ $theme['name'] }}
                                            @if ($activeTheme === $key)<span class="badge badge-success">المعتمدة الآن</span>@endif
                                        </span>
                                        <span class="block text-xs text-muted mt-1 leading-relaxed">{{ $theme['hint'] }}</span>
                                    </span>
                                    <a href="{{ route('home', ['theme' => $key]) }}" target="_blank" rel="noopener" class="btn btn-xs btn-outline shrink-0 gap-1"
                                       title="تفتح الموقع بهذه الواجهة لك وحدك؛ الزوار لا يتأثرون">
                                        <x-admin.icon name="eye" class="w-3.5 h-3.5" /> معاينة
                                    </a>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="col-span-12 lg:col-span-8 card">
                <div class="card-body grid grid-cols-12 gap-5">
                    <div class="col-span-12 md:col-span-6"><x-admin.input name="setting[site_title]" label="اسم الموقع" :value="$s('site_title')" /></div>
                    <div class="col-span-12 md:col-span-6"><x-admin.input name="setting[site_slogan]" label="وصف مختصر" :value="$s('site_slogan')" /></div>
                    <div class="col-span-12"><x-admin.input name="setting[site_description]" label="وصف الموقع" :value="$s('site_description')" /></div>
                    <div class="col-span-12 md:col-span-6"><x-admin.input name="setting[site_url]" label="رابط الموقع" :value="$s('site_url')" dir="ltr" /></div>
                    <div class="col-span-12 md:col-span-6"><x-admin.input name="setting[site_whatsapp]" label="رقم هاتف الواتساب" :value="$s('site_whatsapp')" dir="ltr" /></div>
                    <div class="col-span-12 md:col-span-6"><x-admin.input name="setting[subscribe_email]" label="رابط الاشتراك عبر البريد" :value="$s('subscribe_email')" dir="ltr" /></div>
                    <div class="col-span-12 md:col-span-6"><x-admin.input name="setting[subscribe_whasapp]" label="رابط الاشتراك عبر واتساب" :value="$s('subscribe_whasapp')" dir="ltr" /></div>
                    <div class="col-span-12"><x-admin.input name="setting[unsubscribe]" label="رابط إلغاء الاشتراك" :value="$s('unsubscribe')" dir="ltr" /></div>
                    <div class="col-span-12 md:col-span-6"><x-admin.textarea name="setting[header_code]" label="كود في الهيدر" :value="$s('header_code')" dir="ltr" /></div>
                    <div class="col-span-12 md:col-span-6"><x-admin.textarea name="setting[footer_code]" label="كود في الفوتر" :value="$s('footer_code')" dir="ltr" /></div>
                </div>
            </div>
            <aside class="col-span-12 lg:col-span-4 space-y-6">
                <div class="card">
                    <div class="card-header"><h2 class="card-title">الشعار</h2></div>
                    <div class="card-body space-y-4">
                        <x-admin.dropzone name="logo_file" :current="\App\Support\Media::url($s('site_logo'))" label="اسحب الشعار هنا" />
                        <x-admin.input name="setting[site_logo]" label="أو رابط/مسار الشعار" :value="$s('site_logo')" dir="ltr" />
                        <x-admin.select name="setting[newspaper_name]" label="عرض اسم الجريدة بدل الشعار" :options="$yesNo" :value="$s('newspaper_name')" placeholder="- - -" />
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h2 class="card-title">إغلاق الموقع</h2></div>
                    <div class="card-body space-y-4">
                        <x-admin.select name="setting[close_site]" label="إغلاق الموقع" :options="$yesNo" :value="$s('close_site')" placeholder="- - -"
                                        hint="الموقع العام يعرض رسالة الإغلاق، واللوحة تبقى تعمل." />
                        <x-admin.textarea name="setting[close_site_cause]" label="سبب الإغلاق" :value="$s('close_site_cause')" />
                    </div>
                </div>
            </aside>
        </div>

        @endif

        {{-- ===================== صناديق الرئيسية ===================== --}}
        @if (isset($tabs['home']))
        <div id="tab-home" data-tab-panel @if ($tab !== 'home') hidden @endif class="space-y-4">
            @include('admin.settings._boxes', ['boxes' => $homeBoxes, 'context' => 'home'])
        </div>

        @endif

        {{-- ===================== إعدادات الـ PDF ===================== --}}
        @if (isset($tabs['pdf']))
        <div id="tab-pdf" data-tab-panel @if ($tab !== 'pdf') hidden @endif class="grid grid-cols-12 gap-6 items-start">
            <div class="col-span-12 xl:col-span-8 space-y-6">
                <div class="card">
                    <div class="card-header"><h2 class="card-title">الصفحة</h2></div>
                    <div class="card-body space-y-6">
                        <div class="grid grid-cols-12 gap-5 items-end">
                            <div class="col-span-12 md:col-span-4">
                                <x-admin.select name="setting[pdf_format]" label="حجم الورق" :options="array_combine($pdfFormats, $pdfFormats)" :value="$s('pdf_format') ?: 'A4'" />
                            </div>
                            <div class="col-span-12 md:col-span-8">
                                <p class="form-label">اتجاه الصفحة</p>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach (['P' => ['طولي', '0 0 30 40'], 'L' => ['عرضي', '0 0 40 30']] as $value => [$label, $box])
                                        <label class="choice">
                                            <input type="radio" name="setting[pdf_orientation]" value="{{ $value }}" @checked(($s('pdf_orientation') ?: 'P') === $value)>
                                            <span class="choice-card !flex-row !py-3">
                                                <svg viewBox="{{ $box }}" class="h-8 w-auto text-primary-500" aria-hidden="true"><rect x="1" y="1" width="{{ explode(' ', $box)[2] - 2 }}" height="{{ explode(' ', $box)[3] - 2 }}" rx="3" fill="currentColor" fill-opacity=".12" stroke="currentColor" stroke-width="2"/></svg>
                                                {{ $label }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="form-label">توزيع المحتوى</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <label class="choice">
                                    <input type="radio" name="setting[pdf_pages]" value="0" @checked((string) $s('pdf_pages') !== '1')>
                                    <span class="choice-card !items-start !text-start">
                                        <span class="flex items-center gap-2 text-ink"><x-admin.icon name="paper" class="w-5 h-5 text-primary-500" /> صفحة مستقلة لكل عنصر</span>
                                        <span class="text-xs font-normal">كل خبر (أو تغريدتين) في صفحة بخلفية قسمه. الشكل المعتاد للنشرة.</span>
                                    </span>
                                </label>
                                <label class="choice">
                                    <input type="radio" name="setting[pdf_pages]" value="1" @checked((string) $s('pdf_pages') === '1')>
                                    <span class="choice-card !items-start !text-start">
                                        <span class="flex items-center gap-2 text-ink"><x-admin.icon name="news" class="w-5 h-5 text-primary-500" /> نص متصل</span>
                                        <span class="text-xs font-normal">الأخبار متتابعة وتنتقل للصفحة التالية عند امتلاء الصفحة.</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">الخط</h2>
                            <p class="card-subtitle mt-1">يُستخدم في كل نصوص النشرة.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($pdfFonts === [])
                            <p class="text-sm text-muted">لا توجد خطوط في <code dir="ltr">resources/fonts</code>. شغّل <code dir="ltr">php artisan alnajat:assets</code>.</p>
                        @else
                            <style>
                                @foreach ($pdfFonts as $font => $label)
                                    @font-face { font-family: "pdf-{{ $font }}"; src: url("{{ route('admin.settings.font', $font) }}") format("truetype"); font-display: swap; }
                                @endforeach
                            </style>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                @foreach ($pdfFonts as $font => $label)
                                    <label class="choice">
                                        <input type="radio" name="setting[pdf_font]" value="{{ $font }}" @checked($s('pdf_font') === $font)>
                                        <span class="choice-card !gap-1">
                                            <span class="text-xl text-ink leading-loose" style="font-family: 'pdf-{{ $font }}', sans-serif">نشرة النجاة</span>
                                            <span class="text-xs font-normal" dir="ltr">{{ $label }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <aside class="col-span-12 xl:col-span-4 space-y-6">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">الهوامش</h2>
                            <p class="card-subtitle mt-1">بالمليمتر.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        @php
                            $margin = fn (string $key, string $label) => '<label class="block text-center"><span class="block text-xs text-muted mb-1">'.$label.'</span>'
                                .'<input type="number" min="0" max="100" step="1" name="setting[pdf_margin_'.$key.']" value="'.e(old('setting.pdf_margin_'.$key, $s('pdf_margin_'.$key))).'" class="form-input form-input-sm text-center" placeholder="0"></label>';
                        @endphp
                        <div class="margin-box">
                            <div></div>{!! $margin('top', 'علوي') !!}<div></div>
                            {!! $margin('right', 'أيمن') !!}
                            <div class="margin-page">
                                <div class="border-b border-dashed border-line pb-2">{!! $margin('header', 'الهيدر') !!}</div>
                                <div class="space-y-1.5 py-2" aria-hidden="true">
                                    <div class="h-1.5 rounded bg-line"></div><div class="h-1.5 rounded bg-line w-4/5 ms-auto"></div>
                                    <div class="h-1.5 rounded bg-line"></div><div class="h-1.5 rounded bg-line w-3/5 ms-auto"></div>
                                </div>
                                <div class="border-t border-dashed border-line pt-2">{!! $margin('footer', 'الفوتر') !!}</div>
                            </div>
                            {!! $margin('left', 'أيسر') !!}
                            <div></div>{!! $margin('bottom', 'سفلي') !!}<div></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">الهيدر والفوتر الثابتان</h2>
                            <p class="card-subtitle mt-1">يعملان مع قالب النشرة الأول (النسخة 1) فقط.</p>
                        </div>
                    </div>
                    <div class="card-body space-y-4">
                        <x-admin.switch name="setting[pdf_set_header]" label="اسم الموقع أعلى كل صفحة" :checked="(string) $s('pdf_set_header') === '1'" />
                        <x-admin.switch name="setting[pdf_set_footer]" label="اسم الموقع ورقم الصفحة والتاريخ أسفل كل صفحة" :checked="(string) $s('pdf_set_footer') === '1'" />
                    </div>
                </div>

                <div class="card">
                    <div class="card-body space-y-3">
                        <a href="{{ route('pdf.today') }}" target="_blank" rel="noopener" class="btn btn-primary btn-block gap-2">
                            <x-admin.icon name="eye" class="w-4 h-4" /> معاينة آخر نشرة
                        </a>
                        <p class="text-xs text-muted text-center">احفظ الإعدادات أولاً؛ النشرة تُولَّد من جديد بعد الحفظ.</p>
                    </div>
                </div>
            </aside>
        </div>

        @endif

        {{-- ===================== صناديق الـ PDF ===================== --}}
        @if (isset($tabs['pdf_boxes']))
        <div id="tab-pdf_boxes" data-tab-panel @if ($tab !== 'pdf_boxes') hidden @endif class="space-y-4">
            @include('admin.settings._boxes', ['boxes' => $pdfBoxes, 'context' => 'pdf'])
        </div>
        @endif
    </form>

    {{-- معاينة صندوق (HTML للرئيسية، PDF للنشرة) --}}
    <div class="modal" id="box-preview" hidden data-preview-url="{{ route('admin.settings.preview') }}">
        <div class="modal-backdrop"></div>
        <div class="modal-dialog !w-[min(96vw,80rem)] !my-4">
            <div class="modal-header">
                <div class="min-w-0">
                    <h3 class="font-bold truncate" data-preview-title>معاينة</h3>
                    <p class="text-xs text-muted mt-1">بآخر أخبار القسم المنشورة، وبالإعدادات الحالية قبل الحفظ.</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="#" target="_blank" rel="noopener" class="btn btn-sm btn-ghost gap-1.5" data-preview-newtab><x-admin.icon name="link" class="w-4 h-4" /> نافذة جديدة</a>
                    <button type="button" class="btn btn-icon btn-sm btn-ghost" data-modal-close aria-label="إغلاق"><x-admin.icon name="x" class="w-4 h-4" /></button>
                </div>
            </div>
            <div class="relative bg-surface-2 h-[78vh]">
                <div class="absolute inset-0 grid place-items-center text-sm text-muted" data-preview-loading>جارٍ تحضير المعاينة…</div>
                <iframe class="relative w-full h-full bg-white invisible" title="معاينة الصندوق" data-preview-frame></iframe>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.7/Sortable.min.js"></script>
        <script src="{{ asset('admin/js/boxes.js') }}?v={{ filemtime(public_path('admin/js/boxes.js')) }}"></script>
        <script>
            document.addEventListener('tab:change', function (e) {
                var btn = document.querySelector('[data-tab="' + e.detail.tab + '"]');
                if (btn) document.getElementById('currentTab').value = btn.getAttribute('data-tab-key');
            });
        </script>
    @endpush
</x-admin.layout>
