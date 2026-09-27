{{--
    صناديق الصفحة الرئيسية ($context = home) أو النشرة ($context = pdf).
    كل صندوق: أخبار قسم بقالب عرض، أو بانر، أو كود، أو فارغ. الترتيب بالسحب.
--}}
@php
    $templates = config("alnajat.box_templates.$context", []);
    $kinds = ['news' => 'أخبار قسم', 'banner' => 'بانر', 'code' => 'كود', 'empty' => 'فارغ'];
    $kindOf = fn ($box) => $box->banner_id ? 'banner' : (filled($box->code) ? 'code' : ($box->category_id ? 'news' : 'empty'));
    $isPdf = $context === 'pdf';
@endphp

<div class="card">
    <div class="card-body flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4 min-w-0">
            <span class="stat-icon bg-primary-500/12 text-primary-600 shrink-0"><x-admin.icon :name="$isPdf ? 'pdf' : 'home'" class="w-6 h-6" /></span>
            <div class="min-w-0">
                <h2 class="text-lg font-extrabold">{{ $isPdf ? 'أقسام ملف الـ PDF' : 'صناديق الصفحة الرئيسية' }}</h2>
                <p class="text-sm text-muted mt-1">
                    {{ $isPdf
                        ? 'ترتيب صفحات النشرة بعد الغلاف: كل صندوق يضيف صفحات أخبار قسم بقالبه، أو صفحة بانر، أو كوداً.'
                        : 'ما يظهر في الرئيسية من الأعلى إلى الأسفل. اسحب الصناديق لترتيبها، واختر لكل صندوق نوعه وقالبه.' }}
                </p>
                @unless ($isPdf)
                    @php
                        $liveTheme = \App\Support\Theme::active();
                    @endphp
                    <p class="text-xs text-muted mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span>نفس الصناديق تعمل في الواجهتين؛ الموقع الآن بـ<strong class="text-ink">«{{ \App\Support\Theme::THEMES[$liveTheme]['name'] }}»</strong>، والمعاينة تعرض الصندوق بشكلها.</span>
                        @foreach (\App\Support\Theme::THEMES as $key => $theme)
                            @continue($key === $liveTheme)
                            <a href="{{ route('home', ['theme' => $key]) }}" target="_blank" rel="noopener" class="font-bold text-primary-600 hover:underline">عرض الرئيسية بـ«{{ $theme['name'] }}»</a>
                        @endforeach
                    </p>
                @endunless
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline gap-2" data-tpl-gallery-open="{{ $context }}">
                <x-admin.icon name="palette" class="w-4 h-4" /> معرض القوالب
            </button>
            <a href="{{ $isPdf ? route('pdf.today') : route('home') }}" target="_blank" rel="noopener" class="btn btn-outline gap-2">
                <x-admin.icon name="eye" class="w-4 h-4" /> {{ $isPdf ? 'آخر نشرة' : 'عرض الرئيسية' }}
            </a>
        </div>
    </div>
</div>

<ol class="space-y-3" data-boxes="{{ $context }}">
    @foreach ($boxes as $box)
        @php
            $p = "boxes[$context][{$box->position}]";
            $key = "boxes.$context.{$box->position}";
            $kind = old("$key.kind", $kindOf($box));
            $type = (int) old("$key.type", $box->type ?: array_key_first($templates));
        @endphp
        <li class="box-row" data-box data-context="{{ $context }}">
            <input type="hidden" name="{{ $p }}[position]" value="{{ $box->position }}" data-box-position>
            <input type="hidden" name="{{ $p }}[type]" value="{{ $type }}" data-box-type>

            <div class="box-row-head">
                <span class="box-handle" data-box-handle title="اسحب لتغيير الترتيب"><x-admin.icon name="grip" class="w-5 h-5" /></span>
                <span class="box-num" data-box-num>{{ $loop->iteration }}</span>
                <div class="seg" role="radiogroup" aria-label="نوع الصندوق {{ $loop->iteration }}">
                    @foreach ($kinds as $k => $label)
                        <label><input type="radio" class="kind-{{ $k }}" name="{{ $p }}[kind]" value="{{ $k }}" @checked($kind === $k)><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                <p class="box-summary" data-box-summary></p>
                <span data-kind-panel="news">
                    <button type="button" class="btn btn-sm btn-ghost gap-1.5" data-box-preview>
                        <x-admin.icon name="eye" class="w-4 h-4" /> معاينة
                    </button>
                </span>
            </div>

            <div class="box-row-body">
                <div data-kind-panel="news">
                    <div class="grid grid-cols-12 gap-4 items-end">
                        <div class="col-span-12 lg:col-span-5">
                            <button type="button" class="tpl-picker" data-tpl-open title="تغيير القالب">
                                <span data-tpl-thumb><x-admin.template-thumb :context="$context" :type="$type" /></span>
                                <span class="min-w-0">
                                    <span class="block text-xs text-muted">قالب العرض</span>
                                    <strong class="block truncate" data-tpl-name>{{ $templates[$type]['name'] ?? 'قالب '.$type }}</strong>
                                    <span class="block text-xs font-bold text-primary-600 mt-1">تغيير القالب</span>
                                </span>
                            </button>
                        </div>
                        <div class="col-span-8 lg:col-span-4">
                            <x-admin.select :name="$p.'[category_id]'" label="القسم" :options="$categories" :value="$box->category_id" placeholder="اختر القسم" data-box-category />
                        </div>
                        <div class="col-span-4 lg:col-span-3">
                            <x-admin.input :name="$p.'[items_limit]'" :label="$isPdf ? 'أقصى عدد' : 'عدد الأخبار'" type="number" min="1" max="500" :value="$box->items_limit ?: 5" data-box-limit />
                        </div>
                    </div>
                </div>

                <div data-kind-panel="banner">
                    <div class="grid grid-cols-12 gap-4 items-end">
                        <div class="col-span-12 md:col-span-6">
                            <x-admin.select :name="$p.'[banner_id]'" label="البانر" :options="$banners" :value="$box->banner_id" placeholder="اختر البانر" data-box-banner />
                        </div>
                        <p class="col-span-12 md:col-span-6 text-sm text-muted pb-2">
                            تُدار البانرات من <a href="{{ route('admin.banners.index') }}" class="font-bold text-primary-600 hover:underline">صفحة البانرات</a>. البانر المعطّل لا يظهر.
                        </p>
                    </div>
                </div>

                <div data-kind-panel="code">
                    <x-admin.textarea :name="$p.'[code]'" label="كود HTML" :value="$box->code" rows="4" dir="ltr" class="font-mono text-xs" />
                </div>

                <div data-kind-panel="empty">
                    <p class="text-sm text-muted">هذا الصندوق لا يظهر {{ $isPdf ? 'في النشرة' : 'في الرئيسية' }}.</p>
                </div>
            </div>
        </li>
    @endforeach
</ol>

{{-- معرض القوالب: لاختيار قالب صندوق، أو للاطلاع فقط من زر «معرض القوالب» --}}
<div class="modal" id="tpl-gallery-{{ $context }}" hidden data-tpl-gallery="{{ $context }}">
    <div class="modal-backdrop"></div>
    <div class="modal-dialog !w-[min(94vw,60rem)]">
        <div class="modal-header">
            <div>
                <h3 class="font-bold">قوالب العرض {{ $isPdf ? 'في النشرة' : 'في الموقع' }}</h3>
                <p class="text-xs text-muted mt-1" data-tpl-gallery-hint>اختر قالباً للصندوق، أو اضغط «معاينة» لتراه بآخر أخبار القسم.</p>
            </div>
            <button type="button" class="btn btn-icon btn-sm btn-ghost" data-modal-close aria-label="إغلاق"><x-admin.icon name="x" class="w-4 h-4" /></button>
        </div>
        <div class="modal-body max-h-[70vh] overflow-y-auto scroll-thin space-y-4">
            <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-surface-2 px-4 py-3">
                <label class="text-sm font-bold" for="tpl-cat-{{ $context }}">المعاينة بأخبار قسم:</label>
                <select id="tpl-cat-{{ $context }}" class="form-select form-input-sm w-auto" data-tpl-preview-category>
                    @foreach ($categories as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 {{ $isPdf ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-4">
                @foreach ($templates as $tplType => $tpl)
                    <div class="tpl-option" data-tpl-option="{{ $tplType }}" data-tpl-label="{{ $tpl['name'] }}">
                        <button type="button" class="text-start space-y-2" data-tpl-choose>
                            <x-admin.template-thumb :context="$context" :type="$tplType" />
                            <span class="flex items-center justify-between gap-2">
                                <strong class="text-sm">{{ $tpl['name'] }}</strong>
                                <span class="badge badge-muted">{{ $tplType }}</span>
                            </span>
                            <span class="block text-xs text-muted leading-relaxed">{{ $tpl['hint'] }}</span>
                        </button>
                        <button type="button" class="btn btn-xs btn-ghost gap-1 self-start mt-auto" data-tpl-live>
                            <x-admin.icon name="eye" class="w-3.5 h-3.5" /> معاينة
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
