@extends('layouts.site', ['active' => 'home'])

@php
    use App\Models\Setting;
    use App\Support\ArabicDate;
    $recentPublications ??= collect();
    $latest = $publication;
    $day = request('date') && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) request('date')) ? \Carbon\CarbonImmutable::parse(request('date')) : null;
    $showingAll = request()->has('all');
    $dateLabel = $day ? ArabicDate::long($day) : ($latest?->publication_date ? ArabicDate::long($latest->publication_date) : ArabicDate::long(now()));
    $isToday = ! $day && $latest?->publication_date?->isToday();
    $chips = $boxes->where('kind', 'news')->map(fn ($item) => $item['box']->category)->filter()->unique('id')->take(8);
@endphp

@section('content')
    <section class="wrap hero">
        <div class="hero-text">
            <span class="pill">{{ $showingAll ? 'آخر الأخبار' : ($day ? 'نشرة يوم' : ($isToday ? 'نشرة اليوم' : 'آخر نشرة')) }}</span>
            <h1>{{ Setting::get('site_title') }}<br><span class="hero-date">{{ $dateLabel }}</span></h1>
            <p class="lead">{{ Setting::get('site_description') ?: 'ما نُشر في الصحف والإذاعة والتلفزيون ومواقع التواصل في نشرة واحدة تُقرأ على الموقع أو تُحمَّل ملفاً.' }}</p>
            <div class="actions">
                @if ($latest)
                    <a class="btn btn-primary btn-lg" href="{{ route('publication.pdf', $latest->id) }}" target="_blank">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M12 18v-6M9 15l3 3 3-3"/></svg>
                        تصفّح النشرة PDF</a>
                @endif
                <a class="btn btn-outline btn-lg" href="#news">أخبار {{ $isToday ? 'اليوم' : 'النشرة' }} على الموقع</a>
            </div>
            @if ($chips->isNotEmpty())
                <nav class="chips" aria-label="أقسام النشرة">
                    @foreach ($chips as $category)<a class="chip" href="#cat-{{ $category->id }}">{{ $category->name }}</a>@endforeach
                </nav>
            @endif
        </div>
        @if ($recentPublications->isNotEmpty())
            <div class="hero-covers" aria-label="أغلفة آخر النشرات">
                @foreach ($recentPublications->take(3)->reverse() as $k => $p)
                    <a class="hero-cover pos-{{ $k }}" href="{{ route('publication.pdf', $p->id) }}" target="_blank"
                       title="نشرة {{ $p->publication_date ? ArabicDate::long($p->publication_date) : $p->title }}">
                        @include('site.partials.cover', ['publication' => $p])
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <div id="news">
        @if ($boxes->isEmpty())
            <section class="wrap">
                <div class="empty">
                    <h2>لم تُنشر أخبار {{ $day ? 'هذا اليوم' : 'اليوم' }} بعد</h2>
                    <p>تصفّح آخر الأخبار المنشورة، أو نشرات الأيام السابقة من الأرشيف.</p>
                    <div class="actions center">
                        <a class="btn btn-primary" href="{{ route('home', ['all' => 1]) }}">آخر الأخبار</a>
                        <a class="btn btn-outline" href="{{ route('publications.index') }}">أرشيف النشرات</a>
                    </div>
                </div>
            </section>
        @else
            @include('site.partials.boxes', ['boxes' => $boxes])
        @endif
    </div>

    @if ($recentPublications->count() > 1)
        <section class="wrap">
            <div class="archive-cta">
                <div>
                    <h2>أرشيف النشرات</h2>
                    <p>كل النشرات السابقة مرتبة بالتاريخ، تُفتح على الموقع أو تُحمَّل PDF.</p>
                    <a class="btn btn-outline" href="{{ route('publications.index') }}">تصفح الأرشيف</a>
                </div>
                <div class="archive-cta-covers">
                    @foreach ($recentPublications->take(3) as $p)
                        <a href="{{ route('publication.show', $p->id) }}" class="mini-cover">@include('site.partials.cover', ['publication' => $p])</a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
