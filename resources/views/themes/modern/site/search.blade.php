@extends('layouts.site', ['title' => $term !== '' ? 'نتائج البحث: '.$term : 'البحث'])

@section('content')
    <div class="wrap">
        @include('site.partials.breadcrumb', ['current' => $term !== '' ? 'نتائج البحث' : 'البحث', 'page' => $news?->currentPage() ?? 1])
        <header class="page-head">
            <h1>{{ $term !== '' ? 'نتائج البحث عن «'.$term.'»' : 'البحث في الأخبار' }}</h1>
            @if ($news)<p class="muted">{{ $news->total() > 0 ? number_format($news->total()).' نتيجة' : 'لا توجد نتائج' }}</p>@endif
        </header>
        <form class="search search-lg" role="search" method="get" action="{{ route('search') }}">
            <label for="page-search" class="sr-only">كلمة البحث</label>
            <input id="page-search" type="search" name="s" placeholder="اكتب كلمة أو عبارة…" value="{{ $term }}" required>
            <button type="submit" aria-label="بحث">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </button>
        </form>
    </div>

    @if ($news && $news->isNotEmpty())
        <section class="band band-flush">
            <div class="wrap">
                <div class="list">
                    @foreach ($news as $post)@include('site.partials.news-card', ['post' => $post, 'variant' => 'row', 'showDate' => true])@endforeach
                </div>
                <div class="pager-wrap">{{ $news->links('site.partials.pagination') }}</div>
            </div>
        </section>
    @elseif ($news)
        <div class="wrap"><div class="empty"><h2>لم نجد أخباراً تطابق «{{ $term }}»</h2><p>جرّب كلمة أقصر أو مختلفة.</p></div></div>
    @endif
@endsection
