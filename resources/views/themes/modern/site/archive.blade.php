@extends('layouts.site', ['title' => 'أرشيف النشرات', 'canonical' => route('publications.index'), 'active' => 'archive'])

@php
    use App\Support\ArabicDate;
    use App\Support\Media;
    $groups = $publications->getCollection()->groupBy(fn ($p) => $p->publication_date?->format('Y-m') ?? '');
@endphp

@section('content')
    <div class="wrap">
        @include('site.partials.breadcrumb', ['current' => 'أرشيف النشرات', 'page' => $publications->currentPage()])
        <header class="page-head">
            <h1>أرشيف النشرات</h1>
            <p class="muted">كل النشرات بالأحدث أولاً؛ افتح النشرة على الموقع أو حمّلها PDF.</p>
        </header>

        @if ($publications->isEmpty())
            <div class="empty"><h2>لا توجد نشرات منشورة بعد</h2></div>
        @endif

        @foreach ($groups as $month => $items)
            <section class="archive-month">
                @if ($month !== '')<h2 class="archive-month-title">{{ ArabicDate::monthYear($month.'-01') }}</h2>@endif
                <div class="archive-grid">
                    @foreach ($items as $p)
                        <article class="issue">
                            <a class="issue-cover" href="{{ route('publication.show', $p->id) }}" aria-label="نشرة {{ $p->publication_date ? ArabicDate::long($p->publication_date) : $p->title }}">
                                @include('site.partials.cover', ['publication' => $p])
                            </a>
                            <div class="issue-body">
                                <h3><a href="{{ route('publication.show', $p->id) }}">{{ $p->publication_date ? ArabicDate::long($p->publication_date) : $p->title }}</a></h3>
                                <div class="issue-links">
                                    <a href="{{ route('publication.pdf', $p->id) }}" target="_blank">PDF</a>
                                    <a href="{{ route('publication.pdf', ['id' => $p->id, 'download' => 1]) }}">تحميل</a>
                                    @if ($p->other_file)<a href="{{ Media::url($p->other_file) }}" target="_blank">الملحق</a>@endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="pager-wrap">{{ $publications->links('site.partials.pagination') }}</div>
    </div>
@endsection
