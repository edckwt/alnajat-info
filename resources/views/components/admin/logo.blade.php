{{--
    شعار الموقع من الإعدادات (site_logo)، أو الأيقونة واسم «النجاة» إن لم يُرفع شعار.
      size: sm (ترويسة الجوال) | md (القائمة الجانبية) | lg (صفحة الدخول)
      chip: خلفية بلون الثيم خلف الشعار، للأماكن الفاتحة (الشعار غالباً أبيض النص)
      icon/text: تنسيق البديل (مربع الأيقونة، والاسم)
--}}
@props([
    'size' => 'md',
    'chip' => false,
    'icon' => 'w-9 h-9 rounded-xl bg-primary-600 text-white',
    'text' => 'font-extrabold text-lg',
])
@php($logo = \App\Support\Brand::logoUrl())
@if ($logo)
    {{-- أسماء الأصناف كاملة حتى يراها Tailwind عند البناء --}}
    <span {{ $attributes->class(['brand-logo', match ($size) { 'sm' => 'brand-logo-sm', 'lg' => 'brand-logo-lg', default => 'brand-logo-md' }, 'is-chip' => $chip]) }}>
        <img src="{{ $logo }}" alt="{{ \App\Support\Brand::name() }}" title="{{ \App\Support\Brand::name() }}">
    </span>
@else
    <span {{ $attributes->class(['brand-fallback flex items-center gap-3']) }}>
        <span class="{{ $icon }} grid place-items-center shrink-0">
            <x-admin.icon name="logo" class="w-5 h-5" />
        </span>
        <span class="brand-text {{ $text }}">{{ \App\Support\Brand::name() }}</span>
    </span>
@endif
