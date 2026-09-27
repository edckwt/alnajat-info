{{-- صفحات خيار قالب: المصمَّم يُرسم من تصميمه، والقديم من صوره --}}
@if ($option['template'])
    <span class="grid grid-cols-3 gap-2">
        @foreach (['cover' => 'الغلاف', 'section' => 'صفحة قسم', 'last' => 'الختام'] as $page => $label)
            <span class="block space-y-1">
                <x-admin.pdf-design-page :design="$option['design']" :page="$page" />
                <span class="block text-center text-[11px] text-muted">{{ $label }}</span>
            </span>
        @endforeach
    </span>
@else
    @include('admin.publications._template-pages', ['template' => $option['legacy']])
@endif
