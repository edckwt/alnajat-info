{{--
    شعار الجريدة (مصدر الخبر): الشعار على خلفية بيضاء، أو الاسم إن لم يكن للجريدة شعار
    أو اختير «عرض اسم الجريدة بدل الشعار» في الإعدادات.
    $paper: Newspaper|null، $size: sm (البطاقات) | md (القوائم) | lg (صفحة الخبر)، $label: نص قبله (اختياري)
--}}
@if ($paper = $paper ?? null)
    @php
        $size ??= 'sm';
        $paperLogo = (string) \App\Models\Setting::get('newspaper_name') !== '1' ? \App\Support\Media::url($paper->logo) : null;
    @endphp
    <span class="paper paper-{{ $size }} {{ $paperLogo ? 'has-logo' : '' }}" title="{{ $paper->name }}">
        @isset($label)<span class="paper-label">{{ $label }}</span>@endisset
        @if ($paperLogo)
            <img src="{{ $paperLogo }}" alt="{{ $paper->name }}" loading="lazy" decoding="async">
        @endif
        <span class="paper-name {{ $paperLogo && $size === 'sm' ? 'sr-only' : '' }}">{{ $paper->name }}</span>
    </span>
@endif
