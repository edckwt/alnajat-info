@extends('layouts.site', ['title' => $title, 'active' => (int) request()->route('id') ?: null])

@section('content')
    <div class="wrap">
        @include('site.partials.breadcrumb', ['current' => $title, 'page' => $news->currentPage()])
        <header class="page-head">
            <h1>{{ $title }}</h1>
            @if ($news->total() > 0)<p class="muted">{{ number_format($news->total()) }} خبر، الأحدث أولاً</p>@endif
        </header>
    </div>

    @if ($news->isEmpty())
        <div class="wrap"><div class="empty"><h2>لا توجد أخبار في هذا القسم بعد</h2></div></div>
    @else
        <section class="band band-flush">
            <div class="wrap">
                <div class="grid grid-3">
                    @foreach ($news as $post)@include('site.partials.news-card', ['post' => $post, 'showDate' => true])@endforeach
                </div>
                <div class="pager-wrap">{{ $news->links('site.partials.pagination') }}</div>
            </div>
        </section>
    @endif
@endsection
