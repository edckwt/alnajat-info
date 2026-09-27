@extends('layouts.site', [
    'title' => $news->title,
    'description' => $news->description,
    'image' => \App\Support\Media::url($news->image),
    'canonical' => route('news.show', $news->id),
    'active' => $news->categories->first()?->id,
])

@php
    use App\Support\ArabicDate;
    use App\Support\Media;
    $sameDay ??= collect();
    $dayPublication ??= null;
    $paper = $news->newspaper;
    $paperLogo = Media::thumb($paper?->logo, 'small');
    $shareUrl = route('news.show', $news->id);
    $shareText = rawurlencode($news->title);
    $shareUrlEnc = rawurlencode($shareUrl);
    parse_str((string) parse_url((string) $news->video_url, PHP_URL_QUERY), $videoArgs);
    $ytId = $videoArgs['v'] ?? (preg_match('~(youtu\.be/|embed/|shorts/)([^#&?/]+)~', (string) $news->video_url, $m) ? $m[2] : null);
    $audioDirect = filled($news->sound_url) && preg_match('/\.(mp3|m4a|ogg|wav|aac)(\?|$)/i', $news->sound_url);
    $desc = trim((string) $news->description);
    $isProject = (int) $news->type === 5 && filled($news->source_url);
@endphp

@section('content')
    <div class="wrap">
        @include('site.partials.breadcrumb', [
            'parents' => $news->categories->map(fn ($c) => ['title' => $c->name, 'url' => route('category.show', $c->id)])->all(),
            'current' => $news->title,
        ])
    </div>

    <div class="wrap article-layout">
        <article class="article">
            <header class="article-head">
                <div class="tags">
                    @foreach ($news->categories as $category)<a class="tag tag-primary" href="{{ route('category.show', $category->id) }}">{{ $category->name }}</a>@endforeach
                    @if ($paper)<span class="tag">{{ $paper->name }}</span>@endif
                    @if ($news->published_date)<a class="tag" href="{{ route('home', ['date' => $news->published_date->toDateString()]) }}">{{ ArabicDate::long($news->published_date) }}</a>@endif
                </div>
                <h1>{{ $news->title }}</h1>
                @if ($desc !== '' && $desc !== trim($news->title))
                    <p class="lead">{{ $desc }}</p>
                @endif
            </header>

            @if ($ytId)
                <div class="article-video">
                    <iframe src="https://www.youtube-nocookie.com/embed/{{ $ytId }}?rel=0" title="{{ $news->title }}" loading="lazy"
                            allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            @elseif ($news->image)
                <figure class="article-figure">
                    <a href="{{ Media::url($news->image) }}" target="_blank" title="عرض الصورة بالحجم الكامل">
                        <img src="{{ Media::url($news->image) }}" alt="{{ $news->title }}">
                    </a>
                    @if ($paper && ($paperLogo && (string) \App\Models\Setting::get('newspaper_name') !== '1'))
                        <span class="paper-badge"><img src="{{ $paperLogo }}" alt="{{ $paper->name }}"></span>
                    @endif
                </figure>
            @endif

            @if ($audioDirect)
                <div class="article-audio">
                    <audio controls preload="none" src="{{ $news->sound_url }}"></audio>
                </div>
            @endif

            @if (filled($news->body))
                <div class="prose">{!! $news->body !!}</div>
            @endif

            <div class="article-actions">
                <div class="actions">
                    @if ($isProject)
                        <a class="btn btn-amber" href="{{ $news->source_url }}" target="_blank" rel="noopener">تبرع الآن</a>
                    @elseif (filled($news->source_url))
                        <a class="btn btn-primary" href="{{ $news->source_url }}" target="_blank" rel="noopener">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/></svg>
                            طالع المصدر</a>
                    @endif
                    @if (filled($news->sound_url) && ! $audioDirect)
                        <a class="btn btn-amber" href="{{ $news->sound_url }}" target="_blank" rel="noopener">استمع الآن</a>
                    @endif
                    @if (filled($news->video_url) && ! $ytId)
                        <a class="btn btn-amber" href="{{ $news->video_url }}" target="_blank" rel="noopener">شاهد الآن</a>
                    @endif
                    @if (filled($news->tweet_url))
                        <a class="btn btn-outline" href="{{ $news->tweet_url }}" target="_blank" rel="noopener">عرض المنشور</a>
                    @endif
                    <a class="btn btn-outline" href="{{ route('news.pdf', $news->id) }}" target="_blank">نسخة PDF</a>
                </div>
                <div class="share">
                    <span>شارك:</span>
                    <a href="https://api.whatsapp.com/send?text={{ $shareText }}%20{{ $shareUrlEnc }}" target="_blank" rel="noopener" aria-label="مشاركة عبر واتساب">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 0 1-13.3 7.9L3 21l1.1-4.6A9 9 0 1 1 21 12z"/></svg></a>
                    <a href="https://twitter.com/intent/tweet?text={{ $shareText }}&amp;url={{ $shareUrlEnc }}" target="_blank" rel="noopener" aria-label="مشاركة على X"><b>X</b></a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrlEnc }}" target="_blank" rel="noopener" aria-label="مشاركة على فيسبوك"><b>f</b></a>
                    <button type="button" data-copy="{{ $shareUrl }}" aria-label="نسخ الرابط">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg></button>
                </div>
            </div>
        </article>

        <aside class="aside">
            @if ($sameDay->isNotEmpty())
                <div class="panel">
                    <h2>من نفس النشرة</h2>
                    @foreach ($sameDay as $post)@include('site.partials.news-card', ['post' => $post, 'variant' => 'mini'])@endforeach
                </div>
            @endif
            @if ($dayPublication)
                <div class="panel panel-primary">
                    <a class="mini-cover wide" href="{{ route('publication.pdf', $dayPublication->id) }}" target="_blank">@include('site.partials.cover', ['publication' => $dayPublication])</a>
                    <h2>نشرة {{ $dayPublication->publication_date ? ArabicDate::long($dayPublication->publication_date) : $dayPublication->title }}</h2>
                    <div class="actions">
                        <a class="btn btn-light btn-sm" href="{{ route('publication.show', $dayPublication->id) }}">تصفّح النشرة كاملة</a>
                        <a class="btn btn-ghost-light btn-sm" href="{{ route('publication.pdf', $dayPublication->id) }}" target="_blank">PDF</a>
                    </div>
                </div>
            @endif
        </aside>
    </div>
@endsection
