{{-- صفحات قالب نشرة: الغلاف وصفحة قسم والخاتمة، بنسبة A4 --}}
<span class="grid grid-cols-3 gap-2">
    @foreach (['cover' => 'الغلاف', 'section' => 'صفحة قسم', 'last' => 'الخاتمة'] as $key => $label)
        <span class="block space-y-1">
            <span class="block aspect-[210/297] rounded-lg border border-line bg-surface-2 overflow-hidden shadow-card">
                @if ($template[$key])
                    <img src="{{ asset($template[$key]) }}" alt="{{ $label }}" loading="lazy" class="w-full h-full object-cover object-top">
                @else
                    <span class="w-full h-full grid place-items-center text-[10px] text-faint">لا صورة</span>
                @endif
            </span>
            <span class="block text-center text-[11px] text-muted">{{ $label }}</span>
        </span>
    @endforeach
</span>
