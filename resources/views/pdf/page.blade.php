{{-- pdf_template القديمة: صفحة بخلفية القسم وعنوانه --}}
<div class="{{ $pageClass }}">
    <div class="radio_page_title">
        @if ($plainTitle)
            <h1><a href="{{ route('category.show', $category->id) }}?open=pdf">{{ $category->name }}</a></h1>
        @else
            <div class="top_page_container"><h1><a href="{{ route('category.show', $category->id) }}?open=pdf">{{ $category->name }}</a></h1></div>
        @endif
    </div>
    @foreach ($sections as $section)
        <div class="radio_page_content">{!! $section !!}</div>
    @endforeach
</div>
