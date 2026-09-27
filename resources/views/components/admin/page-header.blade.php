@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => 'عودة'])
<div class="flex flex-wrap items-start justify-between gap-3">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-primary-600 hover:underline mb-2">
                <x-admin.icon name="chevronStart" class="w-3.5 h-3.5 flip-rtl" /> {{ $backLabel }}
            </a>
        @endif
        <h1 class="text-2xl font-extrabold">{{ $title }}</h1>
        @if ($subtitle)<p class="text-sm text-muted mt-1.5">{{ $subtitle }}</p>@endif
    </div>
    @if (trim($slot) !== '')
        <div class="flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</div>
