@extends('layouts.site', ['title' => $title, 'active' => (int) request()->route('id') ?: null])

@php
    // شبكي (الافتراضي) أو قائمة؛ الاختيار يُحفظ للزائر (CategoryController::layout)
    $layout = ($layout ?? 'grid') === 'list' ? 'list' : 'grid';
@endphp

@section('content')
    <div class="wrap">
        @include('site.partials.breadcrumb', ['current' => $title, 'page' => $news->currentPage()])
        <header class="page-head page-head-row">
            <div class="page-head-text">
                <h1>{{ $title }}</h1>
                @if ($news->total() > 0)<p class="muted">{{ number_format($news->total()) }} خبر، الأحدث أولاً</p>@endif
            </div>
            @if ($news->total() > 0)
                <div class="view-switch" role="group" aria-label="طريقة العرض">
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" @class(['is-active' => $layout === 'grid']) aria-pressed="{{ $layout === 'grid' ? 'true' : 'false' }}" rel="nofollow">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                        <span>شبكي</span>
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" @class(['is-active' => $layout === 'list']) aria-pressed="{{ $layout === 'list' ? 'true' : 'false' }}" rel="nofollow">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="6" height="6" rx="1.5"/><rect x="3" y="14" width="6" height="6" rx="1.5"/><path d="M13 6h8M13 9h5M13 16h8M13 19h5"/></svg>
                        <span>قائمة</span>
                    </a>
                </div>
            @endif
        </header>
    </div>

    @if ($news->isEmpty())
        <div class="wrap"><div class="empty"><h2>لا توجد أخبار في هذا القسم بعد</h2></div></div>
    @else
        <section class="band band-flush">
            <div class="wrap">
                @if ($layout === 'list')
                    <div class="news-list">
                        @foreach ($news as $post)@include('site.partials.news-card', ['post' => $post, 'variant' => 'row', 'showDate' => true])@endforeach
                    </div>
                @else
                    <div class="grid grid-3">
                        @foreach ($news as $post)@include('site.partials.news-card', ['post' => $post, 'variant' => 'card', 'showDate' => true])@endforeach
                    </div>
                @endif
                <div class="pager-wrap">{{ $news->links('site.partials.pagination') }}</div>
            </div>
        </section>
    @endif
@endsection
