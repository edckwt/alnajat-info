{{--
    صندوق أخبار بقالب عرض (1 إلى 8) — نفس HTML دالة news_template() في الموقع القديم،
    حتى يعمل ملف css/style.css كما هو.
    $type, $category (أو null), $posts, $showTitle
--}}
@php
    use App\Support\Media;
    $showTitle = $showTitle ?? true;
    $sections = [1 => 'sectinon-a', 2 => 'social', 3 => 'radio', 4 => 'najat-tv', 5 => 'charity-news', 6 => 'charity-news', 7 => 'sectinon-a', 8 => 'sectinon-a'];
    $showPaperName = (string) \App\Models\Setting::get('newspaper_name') === '1';
    $youtube = function (?string $url): ?string {
        if (blank($url)) return null;
        parse_str((string) parse_url($url, PHP_URL_QUERY), $args);
        if (! empty($args['v'])) return $args['v'];
        return preg_match('~(youtu\.be/|v/|u/\w/|embed/|watch\?v=|&v=)([^#&?]*)~', $url, $m) ? $m[2] : null;
    };
@endphp
@if (isset($sections[$type]) && $posts->isNotEmpty())
<section class="{{ $sections[$type] }}">
    <div class="container">
        @if ($category && $showTitle && $type !== 7)
            <div class="row">
                <div class="the-title">
                    <h2><a href="{{ route('category.show', $category->id) }}">{{ $category->name }}</a></h2>
                    <span><img src="{{ asset('images/title-devider.png') }}" alt="{{ $category->name }}"></span>
                </div>
            </div>
        @endif

        @if ($type === 6)<div class="row">@endif

        @foreach ($type === 2 ? $posts->chunk(2) : [$posts] as $group)
            @if ($type === 2)<div class="row">@endif
            @foreach ($group as $post)
                @php
                    $url = route('news.show', $post->id);
                    $paperLogo = Media::url($post->newspaper?->logo);
                    $paperName = $post->newspaper?->name;
                @endphp
                @switch($type)
                    @case(1)
                    @case(5)
                        <div class="row">
                            <div class="largeimage">
                                @if ($post->image)<img src="{{ Media::thumb($post->image, 'large') }}" alt="{{ $post->title }}" title="{{ $post->title }}" class="w-100">@endif
                                @include('site.partials.paper-logo')
                            </div>
                            <div class="news-title"><h2>{{ $post->title }}</h2></div>
                            <div class="news-excerpet">
                                <p>{{ $type === 1 ? \Illuminate\Support\Str::words((string) $post->description, 60, '...') : $post->description }} <a href="{{ $url }}" class="read-more">أكمل القراءة</a></p>
                            </div>
                        </div>
                        @break
                    @case(2)
                        <div class="col-12 col-md-6">
                            <div class="thumbnail circle"><div class="circle-div"><img src="{{ $paperLogo ?: asset('images/logo.png') }}" alt="{{ $post->title }}"></div></div>
                            <span class="social-icon"><i class="fab fa-twitter"></i></span>
                            <div class="tweet-text">
                                <p>{{ $post->description }}</p>
                                <a class="btn btn-large btn-default read-more-button" target="_blank" href="{{ $post->tweet_url ?: '#' }}">رابط التغريدة</a>
                            </div>
                        </div>
                        @break
                    @case(3)
                        <div class="row">
                            <div class="col-3 col-md-3">
                                <a href="{{ $url }}"><img src="{{ $post->image ? Media::thumb($post->image, 'xsmall') : asset('images/njat-radio.png') }}" alt="{{ $post->title }}" class="w-100"></a>
                            </div>
                            <div class="col-9 col-md-9">
                                <div class="news-title"><h2><a href="{{ $url }}">{{ $post->title }}</a></h2></div>
                                @if (filled($post->description) && $post->description !== $post->title)
                                    <p class="radio-desc">{{ $post->description }}</p>
                                @endif
                                @if (filled($post->sound_url))
                                    @if (preg_match('/\w+\.mp[34]/', $post->sound_url))
                                        <span class="radio-listen pull-left">استمع الآن <span id="b_x_{{ $post->id }}" onclick="playAudio('x_{{ $post->id }}', '{{ $post->sound_url }}')"><i class="fa fa-play"></i></span></span>
                                        <div id="x_{{ $post->id }}"></div>
                                    @else
                                        <div class="radio-listen-contaner"><a class="radio-listen pull-left" target="_blank" href="{{ $post->sound_url }}">استمع الآن <i class="fas fa-link"></i></a></div>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @break
                    @case(4)
                        @php($ytId = $youtube($post->video_url))
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <h3>{{ $post->title }}</h3>
                                <p class="text">{{ $post->description }}</p>
                                <a class="radio-listen watch" href="{{ $post->video_url }}">شاهد الآن <i class="fa fa-play"></i></a>
                            </div>
                            <div class="col-12 col-md-6">
                                @if ($ytId)
                                    <iframe width="100%" height="315" src="https://www.youtube.com/embed/{{ $ytId }}?rel=0&showinfo=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                                @endif
                            </div>
                        </div>
                        @break
                    @case(6)
                        <div class="col-12 col-md-6">
                            <div class="largeimage">
                                @if ($post->image)<img src="{{ Media::thumb($post->image, 'medium') }}" alt="{{ $post->title }}" class="w-100 pdf-img-tpl-5">@endif
                                @include('site.partials.paper-logo')
                            </div>
                            <div class="news-title"><h2>{{ $post->title }}</h2></div>
                            <div class="news-excerpet"><p>{{ $post->description }} <a href="{{ $url }}" class="read-more">أكمل القراءة</a></p></div>
                        </div>
                        @break
                    @case(7)
                        <div class="row">
                            <div class="col-md-3 col-sm-12 col-12">
                                @if ($post->image)<img src="{{ Media::thumb($post->image, 'medium') }}" alt="{{ $post->title }}" class="w-100 img-thumbnail archive-img">@endif
                            </div>
                            <div class="col-md-9 col-sm-12 col-12">
                                <div class="news-title"><h3><a href="{{ $url }}">{{ $post->title }}</a></h3></div>
                                <div class="news-excerpet"><p>{{ $post->description }} <a href="{{ $url }}" class="read-more">أكمل القراءة</a></p></div>
                                @if (filled($post->sound_url))
                                    <div class="source mb-3"><a class="btn btn-warning" role="button" target="_blank" href="{{ $post->sound_url }}"><i class="fas fa-headphones"></i> استمع الآن</a></div>
                                @endif
                            </div>
                        </div>
                        @break
                    @case(8)
                        <div class="row">
                            <div class="largeimage">
                                @if ($post->image)<img src="{{ Media::url($post->image) }}" alt="{{ $post->title }}" class="w-100">@endif
                                @include('site.partials.paper-logo')
                            </div>
                        </div>
                        @break
                @endswitch
            @endforeach
            @if ($type === 2)</div>@endif
        @endforeach

        @if ($type === 6)</div>@endif

        {{ $pagination ?? '' }}
    </div>
</section>
@endif
