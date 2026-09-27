{{-- ملف PDF لخبر واحد (news_show القديمة مع read=pdf) --}}
<h1>{{ $news->title }}</h1>
@if ($news->image)<img src="{{ \App\Services\PdfBuilder::src($news->image) }}" alt="{{ $news->title }}" class="w-100">@endif
{!! $news->body !!}
<p style="margin-top: 15px;">المصدر: <a href="{{ route('news.show', $news->id) }}">{{ route('news.show', $news->id) }}</a></p>
