{{--
    news_template_pdf القديمة: HTML عنصر خبر في النشرة حسب القالب (1 إلى 9).
    الصور بمسارات محلية (PdfBuilder::src)، والروابط تعود للموقع مع ?open=pdf لعدّ مشاهدات الـ PDF.
--}}
@php
    use App\Services\PdfBuilder;
    use App\Support\Media;
    $img = fn (string $file) => PdfBuilder::src('images/'.$file);
    $wrappers = [2 => 'social', 3 => 'radio', 4 => 'najat-tv', 5 => 'charity-news', 6 => 'charity-news'];
    $wrapper = $wrappers[$type] ?? 'sectinon-a';
@endphp
<section class="{{ $wrapper }}">
    <div class="container">
        @if ($type === 2 || $type === 6)<div class="row">@endif
        @foreach ($posts as $post)
            @php
                $url = route('news.show', $post->id).'?open=pdf';
                $image = PdfBuilder::src($post->image);
                $medium = PdfBuilder::src(Media::thumb($post->image, 'medium'));
                $xsmall = PdfBuilder::src(Media::thumb($post->image, 'xsmall'));
                $logo = PdfBuilder::src($post->newspaper?->logo);
                $paper = $post->newspaper?->name;
                $tweet = $post->tweet_url ?: '#';
                if (blank($post->image)) {
                    $social = str_contains((string) $post->tweet_url, 'instagram') ? $img('pdf-instagram.jpg')
                        : (str_contains((string) $post->tweet_url, 'facebook') ? $img('pdf-facebook.jpg') : $img('pdf-twitter.jpg'));
                    $tweetLabel = str_contains((string) $post->tweet_url, 'facebook')
                        ? '<img alt="تصفح الموضوع" src="'.$img('browse-post-ar.png').'">'
                        : '<img alt="طالع المصدر" src="'.$img('source-white-ar.png').'">';
                } else {
                    $social = $medium;
                    $tweetLabel = '<img alt="طالع المصدر" src="'.$img('source-white-ar.png').'">';
                }
                $readMore = '<img alt="أكمل القراءة" src="'.$img('read-more-ar.png').'">';
                $source = '<img alt="طالع المصدر" src="'.$img('source-ar-2.png').'">';
                $listen = '<img alt="استمع الآن" src="'.$img('listen-now-ar.png').'">';
                $watch = '<img alt="شاهد الآن" src="'.$img('watch-now-ar.png').'">';
                $donate = '<img alt="تبرع الآن" src="'.$img('donate-ar.png').'">';
            @endphp
            @switch($type)
                @case(2)
                    <div class="col-12 col-md-6">
                        <div class="thumbnail"><div class="circle-div"><img src="{{ $social }}" alt="{{ $post->title }}"></div></div>
                        <div class="tweet-text">
                            <p>{{ \Illuminate\Support\Str::limit((string) $post->description, 400) }}</p>
                            <div class="social-contaner"><a target="_blank" href="{{ $tweet }}">{!! $tweetLabel !!}</a></div>
                        </div>
                    </div>
                    @break
                @case(3)
                    <div class="row-devider">
                        <div class="col-3"><img src="{{ $post->image ? $xsmall : $img('njat-radio.png') }}" alt="{{ $post->title }}" class="w-100"></div>
                        <div class="col-9">
                            <p class="radio-desc">{{ $post->description }}</p>
                            @if (filled($post->sound_url))
                                <div class="radio-listen-contaner"><a class="radio-listen pull-left" target="_blank" href="{{ $url }}">{!! $listen !!}</a></div>
                            @endif
                        </div>
                    </div>
                    @break
                @case(4)
                    <div class="row">
                        <div class="col-6">
                            <h3><a href="{{ $url }}">{{ $post->title }}</a></h3>
                            <p class="text">{{ \Illuminate\Support\Str::limit((string) $post->description, 300) }}</p>
                            <div class="watch-contaner"><a class="radio-listen watch" href="{{ $url }}">{!! $watch !!}</a></div>
                        </div>
                        <div class="col-6">@if ($xsmall)<img src="{{ $xsmall }}" alt="{{ $post->title }}" class="w-100">@endif</div>
                    </div>
                    @break
                @case(5)
                    <div class="row">
                        <div class="largeimage">@if ($image)<img src="{{ $image }}" alt="{{ $post->title }}" class="w-100">@endif</div>
                        <div class="news-title"><h2>{{ $post->title }}</h2></div>
                        <div class="news-excerpet"><p>{{ $post->description }} <a href="{{ $url }}" class="read-more">{!! $readMore !!}</a></p></div>
                    </div>
                    @break
                @case(6)
                    <div class="col-12 col-md-6">
                        <div class="largeimage">@if ($image)<img src="{{ $image }}" alt="{{ $post->title }}" class="w-100 pdf-img-tpl-5">@endif</div>
                        <div class="news-title"><h2>{{ $post->title }}</h2></div>
                        <div class="news-excerpet"><p>{{ $post->description }} <a href="{{ $url }}" class="read-more">{!! $readMore !!}</a></p></div>
                    </div>
                    @break
                @case(7)
                    <div class="row-devider">
                        <div class="col-md-3 col-sm-12 col-12">@if ($medium)<img src="{{ $medium }}" alt="{{ $post->title }}" class="w-100 img-thumbnail archive-img">@endif</div>
                        <div class="col-md-9 col-sm-12 col-12">
                            <div class="news-title"><h3>{{ $post->title }}</h3></div>
                            <div class="news-excerpet"><p>{{ $post->description }} <a href="{{ $url }}" class="read-more">{!! $readMore !!}</a></p></div>
                        </div>
                    </div>
                    @break
                @case(8)
                @case(9)
                    <div class="row">
                        @if ($logo)<div class="magazineLogo"><img src="{{ $logo }}" alt="{{ $paper }}" title="{{ $paper }}"></div>@endif
                        @if ($type === 8)
                            <div class="just-image">@if ($image)<img src="{{ $image }}" alt="{{ $post->title }}" class="w-100">@endif</div>
                        @else
                            <div class="largeimage"><a href="{{ $post->source_url }}">@if ($image)<img src="{{ $image }}" alt="{{ $post->title }}" class="w-100">@endif</a></div>
                        @endif
                        @unless ($post->hide_title)<div class="news-title"><h2>{{ $post->title }}</h2></div>@endunless
                        @unless ($post->hide_description)
                            <div class="news-excerpet"><p>{{ $post->description }}@unless ($post->hide_more) <a href="{{ $url }}" class="read-more">{!! $readMore !!}</a>@endunless</p></div>
                        @endunless
                        @if (filled($post->source_url))
                            @if ($type === 8)
                                <div class="source"><p><a href="{{ $post->source_url }}">{!! $source !!}</a></p></div>
                            @else
                                <div class="donation_url"><p><a href="{{ $post->source_url }}">{!! $donate !!}</a></p></div>
                            @endif
                        @endif
                        @if ($type === 8 && filled($post->tweet_url))
                            <div class="show_tweet"><p><a target="_blank" href="{{ $tweet }}">{!! $tweetLabel !!}</a></p></div>
                        @endif
                    </div>
                    @break
                @default
                    {{-- القالب 1 (وأي قالب غير معروف) --}}
                    <div class="row">
                        @if ($logo)<div class="magazineLogo"><img src="{{ $logo }}" alt="{{ $paper }}" title="{{ $paper }}"></div>@endif
                        <div class="largeimage">@if ($image)<img src="{{ $image }}" alt="{{ $post->title }}" class="w-100">@endif</div>
                        @unless ($post->hide_title)<div class="news-title"><h2>{{ $post->title }}</h2></div>@endunless
                        @unless ($post->hide_description)
                            <div class="news-excerpet"><p>{{ $post->description }}@if (! $post->hide_more && blank($post->sound_url)) <a href="{{ $url }}" class="read-more">{!! $readMore !!}</a>@endif</p></div>
                        @endunless
                        @if (filled($post->source_url) && blank($post->sound_url))
                            <div class="source"><p><a href="{{ $post->source_url }}">{!! $source !!}</a></p></div>
                        @endif
                        @if (filled($post->tweet_url))
                            <div class="show_tweet"><p><a target="_blank" href="{{ $tweet }}">{!! $tweetLabel !!}</a></p></div>
                        @endif
                        @if (filled($post->sound_url))
                            <div class="show_sound"><p><a target="_blank" href="{{ $url }}">{!! $listen !!}</a></p></div>
                        @endif
                        @if (filled($post->video_url))
                            <div class="show_tv"><p><a target="_blank" href="{{ $url }}">{!! $watch !!}</a></p></div>
                        @endif
                    </div>
            @endswitch
        @endforeach
        @if ($type === 2 || $type === 6)</div>@endif
    </div>
</section>
