@php
    $editing = $publication->exists;
    $date = $publication->publication_date?->toDateString();
    $selectedVersion = (int) old('pdf_version', $publication->pdf_version ?: config('alnajat.pdf_latest_version'));
    $meta = function (array $t) {
        $from = \App\Support\ArabicDate::monthYear($t['from']);
        $to = \App\Support\ArabicDate::monthYear($t['to']);

        return number_format($t['count']).' نشرة'.($from ? ' · '.($from === $to ? $from : 'من '.$from.' إلى '.$to) : '');
    };

    // كل خيارات القالب: المصمَّمة المعتمدة أولاً، ثم القديمة (css/pdf-vN)
    $options = [];
    foreach ($designs as $design) {
        $options['t'.$design->id] = ['key' => 't'.$design->id, 'template' => $design->id, 'version' => null, 'name' => $design->name,
            'meta' => 'قالب مصمَّم · '.number_format($design->publications_count).' نشرة', 'latest' => $design->is_default,
            'badge' => $design->is_default ? 'الافتراضي' : 'مصمَّم', 'design' => $design->design];
    }
    foreach (array_reverse($templates, true) as $t) {
        $options['v'.$t['version']] = ['key' => 'v'.$t['version'], 'template' => null, 'version' => $t['version'], 'name' => $t['name'],
            'meta' => $meta($t), 'latest' => $t['latest'] && ! $designs->contains('is_default', true), 'badge' => 'v'.$t['version'], 'legacy' => $t];
    }
    $selectedTemplate = old('pdf_template_id', $publication->pdf_template_id);
    $current = $options[$selectedTemplate ? 't'.$selectedTemplate : 'v'.$selectedVersion] ?? $options['v'.$selectedVersion] ?? reset($options);
@endphp
<x-admin.layout :title="$editing ? 'نشرة '.$date : 'نشرة جديدة'" :breadcrumb="['النشرات' => route('admin.publications.index'), ($editing ? $date : 'نشرة جديدة') => null]">
    <x-admin.page-header :title="$editing ? 'نشرة '.$date : 'نشرة جديدة'" :back="route('admin.publications.index')" back-label="عودة للنشرات"
                         :subtitle="$editing ? $newsCount.' خبراً بتاريخ هذه النشرة' : null">
        @if ($editing && $date)
            @can('news.order')
                <a href="{{ route('admin.news.order', ['date' => $date]) }}" class="btn btn-secondary btn-sm gap-2"><x-admin.icon name="sort" class="w-4 h-4" /> ترتيب أخبارها</a>
            @endcan
            @can('news.view')
                <a href="{{ route('admin.news.index', ['date' => $date]) }}" class="btn btn-secondary btn-sm gap-2"><x-admin.icon name="news" class="w-4 h-4" /> أخبارها</a>
            @endcan
            @if (Route::has('publication.pdf'))
                <a href="{{ route('publication.pdf', $publication) }}" target="_blank" class="btn btn-secondary btn-sm gap-2"><x-admin.icon name="pdf" class="w-4 h-4" /> عرض الـ PDF</a>
            @endif
        @endif
    </x-admin.page-header>
    <x-admin.errors />

    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.publications.update', $publication) : route('admin.publications.store') }}" class="grid grid-cols-12 gap-6 items-start">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="col-span-12 lg:col-span-8 space-y-6">
            <div class="card">
                <div class="card-body space-y-5">
                    <x-admin.input name="publication_date" label="تاريخ النشرة" type="date" :value="$date" required />
                    <x-admin.input name="title" label="العنوان" :value="$publication->title" hint="يُترك فارغاً ليكون التاريخ." />
                    <x-admin.textarea name="description" label="الوصف" :value="$publication->description" />
                    <x-admin.input name="url" label="رابط" :value="$publication->url" dir="ltr" icon="link" />
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">نص الغلاف</h2></div>
                <div class="card-body"><textarea id="body" name="body" class="form-textarea" rows="10">{{ old('body', $publication->body) }}</textarea></div>
            </div>
        </div>

        <aside class="col-span-12 lg:col-span-4 space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">النشر</h2></div>
                <div class="card-body space-y-5">
                    <x-admin.switch name="cover" label="إظهار صفحة الغلاف" :checked="(bool) $publication->cover" />
                    @can('publications.publish')
                        <x-admin.switch name="is_active" label="منشورة" :checked="$publication->is_active" />
                    @else
                        <p class="text-sm text-muted">الحالة: <x-admin.status :active="(bool) $publication->is_active" on="منشورة" off="مخفية" /></p>
                    @endcan
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="save" class="w-4 h-4" /> حفظ</button></div>
            </div>
            <div class="card" data-pdf-template>
                <div class="card-header">
                    <h2 class="card-title">قالب الـ PDF</h2>
                    <span class="badge badge-primary" data-tpl-latest @if (! $current['latest']) hidden @endif>{{ $current['template'] ? 'الافتراضي' : 'الأحدث' }}</span>
                </div>
                <div class="card-body space-y-4">
                    <input type="hidden" name="pdf_version" value="{{ $selectedVersion }}" data-tpl-version>
                    <input type="hidden" name="pdf_template_id" value="{{ $current['template'] }}" data-tpl-template>
                    <button type="button" class="block w-full text-start group" data-modal-open="#pdf-templates" title="تغيير القالب">
                        <span class="block" data-tpl-pages>@include('admin.publications._option-pages', ['option' => $current])</span>
                    </button>
                    <div>
                        <p class="font-bold" data-tpl-name>{{ $current['name'] }}</p>
                        <p class="text-xs text-muted mt-0.5" data-tpl-meta>{{ $current['meta'] }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" class="btn btn-secondary btn-sm gap-1.5" data-modal-open="#pdf-templates"><x-admin.icon name="palette" class="w-4 h-4" /> تغيير القالب</button>
                        <button type="button" class="btn btn-outline btn-sm gap-1.5" data-pdf-preview="{{ $current['key'] }}"><x-admin.icon name="eye" class="w-4 h-4" /> معاينة PDF</button>
                    </div>
                    <p class="form-hint">تغيير القالب يغيّر شكل هذه النشرة فقط، والنشرات الأخرى تبقى بقوالبها.</p>
                    @error('pdf_version')<p class="form-error-text">{{ $message }}</p>@enderror
                    @error('pdf_template_id')<p class="form-error-text">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">صورة الغلاف</h2></div>
                <div class="card-body">
                    <x-admin.dropzone name="image_file" :current="\App\Support\Media::url($publication->image)" label="اسحب صورة الغلاف هنا" />
                    @error('image_file')<p class="form-error-text">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">ملحق النشرة</h2></div>
                <div class="card-body space-y-3">
                    <p class="text-xs text-muted">اختياري: ملف PDF يظهر رابطه «ملحق النشرة» في غلاف النشرة.</p>
                    <x-admin.dropzone name="other_file_upload" accept=".pdf,application/pdf" :max-mb="50"
                                      :current="\App\Support\Media::url($publication->other_file)" label="اسحب ملف PDF هنا" />
                    @error('other_file_upload')<p class="form-error-text">{{ $message }}</p>@enderror
                    @if ($publication->other_file)
                        <label class="form-check"><input type="checkbox" class="form-checkbox" name="remove_other_file" value="1"><span class="text-sm">إزالة الملف المرفوع</span></label>
                    @endif
                </div>
            </div>
        </aside>
    </form>

    {{-- معرض قوالب النشرة --}}
    <div class="modal" id="pdf-templates" hidden>
        <div class="modal-backdrop"></div>
        <div class="modal-dialog !w-[min(96vw,72rem)]">
            <div class="modal-header">
                <div>
                    <h3 class="font-bold">قوالب النشرة</h3>
                    <p class="text-xs text-muted mt-1">لكل قالب غلاف وخلفية لصفحات الأقسام وصفحة ختام. اختر قالباً، أو عاينه بأخبار هذه النشرة.</p>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-ghost" data-modal-close aria-label="إغلاق"><x-admin.icon name="x" class="w-4 h-4" /></button>
            </div>
            <div class="modal-body max-h-[72vh] overflow-y-auto scroll-thin">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($options as $option)
                        <div @class(['tpl-option', 'is-selected' => $option['key'] === $current['key']])
                             data-pdf-option="{{ $option['key'] }}" data-template="{{ $option['template'] }}" data-version="{{ $option['version'] }}"
                             data-name="{{ $option['name'] }}" data-meta="{{ $option['meta'] }}" data-latest="{{ $option['latest'] ? 1 : 0 }}">
                            <button type="button" class="text-start space-y-3" data-pdf-choose>
                                <span class="block" data-pdf-pages>@include('admin.publications._option-pages', ['option' => $option])</span>
                                <span class="flex items-center justify-between gap-2">
                                    <strong>{{ $option['name'] }}</strong>
                                    <span class="flex gap-1">
                                        @if ($option['latest'])<span class="badge badge-primary">{{ $option['template'] ? 'الافتراضي' : 'الأحدث' }}</span>@endif
                                        <span class="badge badge-muted">{{ $option['template'] ? 'مصمَّم' : $option['badge'] }}</span>
                                    </span>
                                </span>
                                <span class="block text-xs text-muted">{{ $option['meta'] }}</span>
                            </button>
                            <button type="button" class="btn btn-xs btn-ghost gap-1 self-start" data-pdf-preview="{{ $option['key'] }}">
                                <x-admin.icon name="eye" class="w-3.5 h-3.5" /> معاينة بأخبار هذه النشرة
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- معاينة PDF: الغلاف وأول ثلاثة أقسام بخبر واحد لكل قسم والخاتمة --}}
    <div class="modal" id="pdf-preview" hidden
         data-url="{{ route('admin.publications.preview') }}" data-publication="{{ $publication->id }}">
        <div class="modal-backdrop"></div>
        <div class="modal-dialog !w-[min(96vw,64rem)] !my-4">
            <div class="modal-header">
                <div class="min-w-0">
                    <h3 class="font-bold truncate" data-preview-title>معاينة</h3>
                    <p class="text-xs text-muted mt-1">معاينة سريعة: الغلاف وأول ثلاثة أقسام بخبر واحد لكل قسم ثم الخاتمة.</p>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-ghost" data-modal-close aria-label="إغلاق"><x-admin.icon name="x" class="w-4 h-4" /></button>
            </div>
            <div class="relative bg-surface-2 h-[78vh]">
                <div class="absolute inset-0 grid place-items-center text-sm text-muted" data-preview-loading>جارٍ توليد المعاينة…</div>
                <iframe class="relative w-full h-full invisible" title="معاينة النشرة" data-preview-frame></iframe>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                var card = document.querySelector('[data-pdf-template]');
                var gallery = document.getElementById('pdf-templates');
                var preview = document.getElementById('pdf-preview');
                if (!card || !gallery || !preview || !window.UI) return;

                var frame = preview.querySelector('[data-preview-frame]');
                var loading = preview.querySelector('[data-preview-loading]');

                function choose(option) {
                    card.querySelector('[data-tpl-template]').value = option.dataset.template || '';
                    if (option.dataset.version) card.querySelector('[data-tpl-version]').value = option.dataset.version;
                    card.querySelector('[data-tpl-pages]').innerHTML = option.querySelector('[data-pdf-pages]').innerHTML;
                    card.querySelector('[data-tpl-name]').textContent = option.dataset.name;
                    card.querySelector('[data-tpl-meta]').textContent = option.dataset.meta;
                    var badge = card.querySelector('[data-tpl-latest]');
                    badge.hidden = option.dataset.latest !== '1';
                    badge.textContent = option.dataset.template ? 'الافتراضي' : 'الأحدث';
                    card.querySelector('[data-pdf-preview]').dataset.pdfPreview = option.dataset.pdfOption;
                    gallery.querySelectorAll('[data-pdf-option]').forEach(function (o) { o.classList.toggle('is-selected', o === option); });
                    UI.closeModal(gallery);
                }

                function openPreview(key) {
                    var option = gallery.querySelector('[data-pdf-option="' + key + '"]');
                    if (!option) return;
                    var date = (document.querySelector('input[name=publication_date]') || {}).value || '';
                    var params = new URLSearchParams({ date: date });
                    if (option.dataset.template) params.set('template', option.dataset.template); else params.set('version', option.dataset.version);
                    if (preview.dataset.publication) params.set('publication', preview.dataset.publication);
                    preview.querySelector('[data-preview-title]').textContent = 'معاينة ' + option.dataset.name + (date ? ' — ' + date : '');
                    frame.classList.add('invisible');
                    loading.hidden = false;
                    frame.src = preview.dataset.url + '?' + params.toString();
                    UI.openModal(preview);
                }

                frame.addEventListener('load', function () {
                    if (frame.src && frame.src !== 'about:blank') { frame.classList.remove('invisible'); loading.hidden = true; }
                });
                document.addEventListener('modal:close', function (e) { if (e.detail.modal === preview) frame.src = 'about:blank'; });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && preview.classList.contains('is-open')) { e.stopImmediatePropagation(); UI.closeModal(preview); }
                }, true);

                document.addEventListener('click', function (e) {
                    var t;
                    if ((t = e.target.closest('[data-pdf-choose]'))) { e.preventDefault(); choose(t.closest('[data-pdf-option]')); }
                    else if ((t = e.target.closest('[data-pdf-preview]'))) { e.preventDefault(); openPreview(t.dataset.pdfPreview); }
                });
            })();
        </script>
    @endpush
</x-admin.layout>
