{{-- ترقيم الصفحات للواجهة الجديدة: $paginator->links('site.partials.pagination') --}}
@if ($paginator->hasPages())
    <nav class="pager" aria-label="الصفحات">
        @if ($paginator->onFirstPage())
            <span class="pager-btn is-off" aria-hidden="true">→ السابق</span>
        @else
            <a class="pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">→ السابق</a>
        @endif
        <span class="pager-pages">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager-gap">{{ $element }}</span>
                @elseif (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager-num is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pager-num" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </span>
        @if ($paginator->hasMorePages())
            <a class="pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">التالي ←</a>
        @else
            <span class="pager-btn is-off" aria-hidden="true">التالي ←</span>
        @endif
    </nav>
@endif
