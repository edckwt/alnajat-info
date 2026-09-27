@props([
    'name',
    'accept' => 'image/*',
    'multiple' => false,
    'current' => null,          // رابط الملف الحالي (يُعرض معاينة)
    'label' => 'اسحب الملف وأفلته هنا',
    'hint' => 'أو اضغط للاختيار، أو الصق صورة من الحافظة',
    'maxMb' => 10,
    'aspect' => null,           // نسبة القص المقترحة في محرر الصور (مثل 1 للصورة الشخصية)
    'maxWidth' => 2400,         // الصور الأعرض تُصغَّر تلقائياً قبل الرفع (0 = بلا تصغير)
])
@php($id = 'dz-'.\Illuminate\Support\Str::random(6))
{{--
    رفع بالسحب والإفلات مرتبط بالنموذج نفسه: الملفات المفلتة تُوضع في حقل الملف المخفي
    وتُرسل مع النموذج عند الحفظ (لا رفع منفصل عبر AJAX).
    الصور: زر «تحرير» (قص، تدوير، قلب، تصغير، صيغة وجودة) في public/admin/js/image-editor.js،
    وتُصغَّر تلقائياً إن زاد عرضها عن max-width.
--}}
<div {{ $attributes->merge(['class' => 'space-y-3']) }} data-dropzone data-max-mb="{{ $maxMb }}" data-max-width="{{ $maxWidth }}" @if ($aspect) data-aspect="{{ $aspect }}" @endif>
    <label for="{{ $id }}" class="dropzone !p-6" data-dz-zone tabindex="0">
        <input id="{{ $id }}" type="file" name="{{ $name }}{{ $multiple ? '[]' : '' }}" accept="{{ $accept }}"
               class="sr-only" @if ($multiple) multiple @endif data-dz-input>
        <span class="w-12 h-12 rounded-2xl bg-primary-500/12 text-primary-600 grid place-items-center pointer-events-none">
            <x-admin.icon name="upload" class="w-6 h-6" />
        </span>
        <span class="text-sm font-bold pointer-events-none">{{ $label }}</span>
        <span class="text-xs text-muted pointer-events-none">{{ $hint }} · حتى {{ $maxMb }} ميجابايت</span>
    </label>

    <div class="space-y-2" data-dz-list>
        @if ($current)
            <div class="flex items-center gap-3 rounded-xl border border-line p-2" data-dz-current>
                @if (preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $current))
                    <img src="{{ $current }}" alt="" class="w-14 h-14 rounded-lg object-cover">
                @else
                    <span class="w-14 h-14 rounded-lg bg-surface-2 grid place-items-center text-muted"><x-admin.icon name="pdf" class="w-6 h-6" /></span>
                @endif
                <a href="{{ $current }}" target="_blank" class="text-xs text-muted truncate flex-1" dir="ltr">{{ basename(parse_url($current, PHP_URL_PATH) ?? $current) }}</a>
                <span class="badge badge-muted">الحالي</span>
            </div>
        @endif
    </div>
</div>
