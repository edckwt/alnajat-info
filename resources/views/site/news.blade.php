@extends('layouts.site', [
    'title' => $news->title,
    'description' => $news->description,
    'image' => \App\Support\Media::url($news->image),
    'canonical' => route('news.show', $news->id),
])

@section('header')
    @include('site.partials.header-inner')
@endsection

@section('content')
    @php
        $paperLogo = \App\Support\Media::thumb($news->newspaper?->logo, 'small');
        $paperName = $news->newspaper?->name;
        $showPaperName = false;
        $shareUrl = urlencode(route('news.show', $news->id));
        $shareTitle = urlencode($news->title);
    @endphp
    <section class="sectinon-a">
        <div class="container">
            @include('site.partials.breadcrumb', [
                'parents' => $news->categories->map(fn ($c) => ['title' => $c->name, 'url' => route('category.show', $c->id)])->all(),
                'current' => $news->title,
            ])

            @if ($news->image)
                <div class="largeimage">
                    <img src="{{ \App\Support\Media::url($news->image) }}" alt="{{ $news->title }}" title="{{ $news->title }}" class="w-100">
                    @include('site.partials.paper-logo')
                </div>
            @endif

            <div class="news-title"><h2>{{ $news->title }}</h2></div>

            @if (filled($news->body))
                <div class="news-excerpet">{!! $news->body !!}</div>
            @endif

            @if (filled($news->source_url))
                <div class="row-devider"><div class="source">
                    <a href="{{ $news->source_url }}" title="{{ $paperName }}: {{ $news->title }}" target="_blank" rel="noopener">مصدر الخبر</a>
                </div></div>
            @endif
            @if (filled($news->sound_url))
                <div class="row-devider"><div class="source">
                    <a class="btn btn-warning" role="button" target="_blank" href="{{ $news->sound_url }}"><i class="fas fa-headphones"></i> استمع الآن</a>
                </div></div>
            @endif
            @if (filled($news->video_url))
                <div class="row-devider"><div class="source">
                    <a class="btn btn-warning" role="button" target="_blank" href="{{ $news->video_url }}"><i class="fas fa-film"></i> شاهد الآن</a>
                </div></div>
            @endif

            <div class="share">
                <div class="row">
                    <div class="col-1">شارك</div>
                    <div class="col-1"><a target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}"><i class="fab fa-facebook-square"></i></a></div>
                    <div class="col-1"><a target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text={{ $shareTitle }}&amp;url={{ $shareUrl }}"><i class="fab fa-twitter-square"></i></a></div>
                    <div class="col-1"><a target="_blank" rel="noopener" href="https://www.linkedin.com/shareArticle?mini=true&url={{ $shareUrl }}&title={{ $shareTitle }}"><i class="fab fa-linkedin"></i></a></div>
                    <div class="col-1"><a target="_blank" rel="noopener" href="https://api.whatsapp.com/send?text={{ $shareTitle }}%20{{ $shareUrl }}"><i class="fab fa-whatsapp-square"></i></a></div>
                    <div class="col-1"><a href="mailto:?subject={{ $shareTitle }}&amp;body={{ $shareUrl }}"><i class="fas fa-envelope-square"></i></a></div>
                </div>
            </div>
        </div>
    </section>
@endsection
