@php
    $config = [
        'readonly' => $readonly,
        'urls' => [
            'save' => route('admin.pdf-templates.update', $template),
            'preview' => route('admin.pdf-templates.preview', $template),
            'upload' => route('admin.pdf-templates.upload', $template),
            'asset' => rtrim(asset(''), '/'),
            'uploads' => rtrim(asset(\App\Support\Media::baseUrl()), '/'),
        ],
        'fonts' => collect($fonts)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'url' => route('admin.pdf-templates.font', $key)])->values(),
        'variables' => $variables,
        'formats' => \App\Support\PdfDesign::FORMATS,
        'categories' => $categories,
        'sample' => [
            'date_long' => \App\Support\ArabicDate::long(now()), 'date_hijri' => \App\Support\ArabicDate::hijri(now()),
            'date' => now()->toDateString(), 'day_name' => \App\Support\ArabicDate::dayName(now()),
            'publication_title' => 'نشرة '.now()->toDateString(), 'publication_number' => '612',
            'site_title' => (string) \App\Models\Setting::get('site_title'), 'site_slogan' => (string) \App\Models\Setting::get('site_slogan'),
            'category_name' => 'النجاة في الصحف', 'page' => '3', 'pages' => '18', 'site_url' => url('/'),
            'publication_url' => route('pdf.today'), 'archive_url' => route('publications.index'), 'other_file_url' => '',
            'whatsapp' => (string) \App\Models\Setting::get('site_whatsapp'),
        ],
    ];
@endphp
<x-admin.layout :title="'تصميم: '.$template->name" :breadcrumb="['قوالب النشرة' => route('admin.pdf-templates.index'), $template->name => null]">
    @push('head')
        <style>
            @foreach ($fonts as $key => $label)
                @font-face { font-family: "pdf-{{ $key }}"; src: url("{{ route('admin.pdf-templates.font', $key) }}") format("truetype"); font-display: swap; }
            @endforeach
        </style>
    @endpush

    <div class="designer" data-designer>
        <script type="application/json" data-designer-design>@json($design)</script>
        <script type="application/json" data-designer-config>@json($config)</script>

        {{-- شريط الأدوات --}}
        <div class="card designer-bar">
            <a href="{{ route('admin.pdf-templates.index') }}" class="btn btn-icon btn-sm btn-ghost" title="عودة للقوالب"><x-admin.icon name="chevronStart" class="w-4 h-4 flip-rtl" /></a>
            <input type="text" class="form-input form-input-sm w-64 font-bold" value="{{ $template->name }}" data-designer-name aria-label="اسم القالب" @disabled($readonly)>
            <span @class(['badge', 'badge-success' => $template->isPublished(), 'badge-warning' => ! $template->isPublished()])>{{ $template->isPublished() ? 'معتمد — للقراءة فقط' : 'مسودة' }}</span>

            <div class="seg ms-2" role="tablist" aria-label="صفحات القالب" data-designer-pages>
                @foreach (\App\Support\PdfDesign::PAGES as $key => $label)
                    <label><input type="radio" name="designer-page" value="{{ $key }}" @checked($loop->first)><span>{{ $label }}</span></label>
                @endforeach
            </div>

            <div class="ms-auto flex items-center gap-2">
                @unless ($readonly)
                    <button type="button" class="btn btn-icon btn-sm btn-ghost" data-designer-undo title="تراجع (Ctrl+Z)" aria-label="تراجع"><x-admin.icon name="undo" class="w-4 h-4" /></button>
                    <button type="button" class="btn btn-icon btn-sm btn-ghost" data-designer-redo title="إعادة (Ctrl+Y)" aria-label="إعادة"><x-admin.icon name="redo" class="w-4 h-4" /></button>
                @endunless
                <select class="form-select form-input-sm w-24" data-designer-zoom data-native aria-label="التكبير">
                    <option value="fit">ملاءمة</option><option value="0.5">50%</option><option value="0.75">75%</option><option value="1">100%</option><option value="1.5">150%</option>
                </select>
                <select class="form-select form-input-sm w-56" data-designer-publication aria-label="نشرة المعاينة">
                    <option value="">المعاينة بآخر نشرة فيها أخبار</option>
                    @foreach ($publications as $p)<option value="{{ $p->id }}">{{ $p->publication_date?->toDateString() }} — {{ \Illuminate\Support\Str::limit($p->title, 30) }}</option>@endforeach
                </select>
                <button type="button" class="btn btn-sm btn-outline gap-1.5" data-designer-preview><x-admin.icon name="pdf" class="w-4 h-4" /> معاينة PDF</button>
                @if ($readonly)
                    @can('pdf_templates.create')
                        <form method="POST" action="{{ route('admin.pdf-templates.duplicate', $template) }}">@csrf
                            <button type="submit" class="btn btn-sm btn-primary gap-1.5"><x-admin.icon name="copy" class="w-4 h-4" /> نسخ للتعديل</button>
                        </form>
                    @endcan
                @else
                    <span class="text-xs text-muted w-28 text-center" data-designer-status>محفوظ</span>
                    <button type="button" class="btn btn-sm btn-primary gap-1.5" data-designer-save><x-admin.icon name="save" class="w-4 h-4" /> حفظ</button>
                @endif
            </div>
        </div>

        <div class="designer-body">
            {{-- العناصر والطبقات --}}
            <aside class="designer-side card">
                @unless ($readonly)
                    <div class="designer-section">
                        <p class="designer-title">العناصر</p>
                        <p class="text-xs text-muted mb-3">اسحب عنصراً إلى الصفحة، أو اضغط لإضافته في وسطها.</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['text' => ['نص', 'text'], 'variable' => ['متغير', 'code'], 'image' => ['صورة', 'image'], 'rect' => ['مستطيل', 'square'], 'line' => ['خط', 'minus'], 'qr' => ['رمز QR', 'qr']] as $type => [$label, $icon])
                                <button type="button" class="designer-tool" draggable="true" data-designer-tool="{{ $type }}">
                                    <x-admin.icon :name="$icon" class="w-5 h-5" /> {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endunless
                <div class="designer-section">
                    <p class="designer-title">الطبقات</p>
                    <p class="text-xs text-muted mb-2">الأعلى في القائمة فوق غيره في الصفحة.</p>
                    <ol class="space-y-1" data-designer-layers></ol>
                </div>
            </aside>

            {{-- الصفحة --}}
            <div class="designer-stage" data-designer-stage>
                <div class="designer-page" data-designer-page></div>
            </div>

            {{-- الخصائص --}}
            <aside class="designer-side card" data-designer-props></aside>
        </div>
    </div>

    {{-- معاينة PDF --}}
    <div class="modal" id="designer-preview" hidden>
        <div class="modal-backdrop"></div>
        <div class="modal-dialog !w-[min(96vw,64rem)] !my-4">
            <div class="modal-header">
                <div class="min-w-0">
                    <h3 class="font-bold">معاينة القالب</h3>
                    <p class="text-xs text-muted mt-1">بالتصميم الحالي قبل الحفظ: الغلاف وأول ثلاثة أقسام بخبر واحد لكل قسم ثم الختام.</p>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-ghost" data-modal-close aria-label="إغلاق"><x-admin.icon name="x" class="w-4 h-4" /></button>
            </div>
            <div class="relative bg-surface-2 h-[78vh]">
                <div class="absolute inset-0 grid place-items-center text-sm text-muted text-center px-6" data-preview-loading>جارٍ توليد المعاينة…</div>
                <iframe class="relative w-full h-full invisible" title="معاينة القالب" data-preview-frame></iframe>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('admin/js/pdf-designer.js') }}?v={{ filemtime(public_path('admin/js/pdf-designer.js')) }}"></script>
    @endpush
</x-admin.layout>
