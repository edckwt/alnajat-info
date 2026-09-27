@if ($paginator->hasPages())
    <nav class="pagination" aria-label="التنقل بين الصفحات">
        @if ($paginator->onFirstPage())
            <span class="page-link opacity-40 pointer-events-none" aria-disabled="true"><x-admin.icon name="chevronStart" class="w-4 h-4 flip-rtl" /></span>
        @else
            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="السابق"><x-admin.icon name="chevronStart" class="w-4 h-4 flip-rtl" /></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="page-link pointer-events-none">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="page-link" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="التالي"><x-admin.icon name="chevronEnd" class="w-4 h-4 flip-rtl" /></a>
        @else
            <span class="page-link opacity-40 pointer-events-none" aria-disabled="true"><x-admin.icon name="chevronEnd" class="w-4 h-4 flip-rtl" /></span>
        @endif
    </nav>
@endif
