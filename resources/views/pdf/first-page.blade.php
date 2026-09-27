{{-- pdf_first_page القديمة: التاريخ + ملحق النشرة + رابط الأرشيف. الخلفية من css/pdf-vN.css --}}
<div class="first_page">
    <div class="first_page_date"><p>{{ \App\Support\ArabicDate::long($publication->publication_date ?? now()) }}</p></div>
    <div class="first_page_number_3">
        @if ($publication->other_file)
            <div class="other-file"><a target="_blank" href="{{ \App\Support\Media::url($publication->other_file) }}">ملحق النشرة</a></div>
        @endif
    </div>
    <div class="publications-archive">
        <a href="{{ route('publications.index') }}?open=pdf"><img alt="النشرات السابقة" src="{{ \App\Services\PdfBuilder::src('images/v'.$version.'/archive-ar-2.png') }}"></a>
    </div>
</div>
