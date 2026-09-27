{{--
    صندوق أخبار في الواجهة الجديدة. نفس بيانات الكلاسيكية: $type (قالب العرض 1–8)، $category (أو null)،
    $posts، $showTitle، $pagination. كل قالب له شكل حديث يناسب محتواه:
    1 خبر رئيسي + بطاقات، 5 بطاقات بوصف كامل، 6 عمودان، 7 قائمة، 8 قصاصات، 2 منشورات التواصل،
    3 صوتيات، 4 مرئيات (الأخيران يُعرضان في شريط داكن، ويتجاوران إن جاءا متتاليين).
--}}
@php
    use App\Support\Media;
    $showTitle = ($showTitle ?? true) && $category;
    $kickers = [1 => 'أبرز الأخبار', 2 => 'من مواقع التواصل', 3 => 'استمع', 4 => 'شاهد', 5 => 'تغطيات', 6 => 'تغطيات', 7 => '', 8 => 'قصاصات اليوم'];
    $dark = in_array($type, [3, 4], true);
    $embedded = $embedded ?? false; // داخل شريط مشترك (صوتيات بجانب مرئيات)
    $youtube = function (?string $url): ?string {
        if (blank($url)) return null;
        parse_str((string) parse_url($url, PHP_URL_QUERY), $args);
        if (! empty($args['v'])) return $args['v'];
        return preg_match('~(youtu\.be/|v/|u/\w/|embed/|shorts/|watch\?v=|&v=)([^#&?/]*)~', $url, $m) ? $m[2] : null;
    };
    $platform = function (?string $url): string {
        $url = (string) $url;
        return match (true) {
            str_contains($url, 'instagram') => 'إنستغرام',
            str_contains($url, 'facebook') || str_contains($url, 'fb.') => 'فيسبوك',
            str_contains($url, 'youtube') || str_contains($url, 'youtu.be') => 'يوتيوب',
            str_contains($url, 'tiktok') => 'تيك توك',
            str_contains($url, 'snapchat') => 'سناب شات',
            default => 'X',
        };
    };
@endphp
@if (isset($kickers[$type]) && $posts->isNotEmpty())
@unless ($embedded)<section class="band {{ $dark ? 'band-dark' : '' }}" @if ($category) id="cat-{{ $category->id }}" @endif><div class="wrap">@endunless
    @if ($showTitle)
        <div class="sec-head">
            <div>
                @if ($kickers[$type] !== '')<span class="kicker">{{ $kickers[$type] }}</span>@endif
                <h2><a href="{{ route('category.show', $category->id) }}">{{ $category->name }}</a></h2>
            </div>
            @unless ($embedded)<a class="more" href="{{ route('category.show', $category->id) }}">عرض الكل <span aria-hidden="true">←</span></a>@endunless
        </div>
    @endif

    @php
        // أخبار بنص حقيقي (وصف غير العنوان)؛ القصاصات والإنفوجرافيك بلا نص تُعرض صوراً
        $hasText = fn ($p) => ($d = trim((string) $p->description)) !== '' && $d !== trim((string) $p->title);
        $textual = $posts->filter($hasText)->count();
        $cols = fn (int $n, int $max) => 'grid-'.max(2, min($max, $n));
    @endphp
    @switch($type)
        @case(1)
        @case(5)
        @case(6)
            @if ($textual === 0)
                {{-- قصاصات: صورة واحدة كاملة، أو شبكة صور --}}
                @if ($posts->count() === 1)
                    <div class="figure-wrap">@include('site.partials.news-card', ['post' => $posts->first(), 'variant' => 'figure'])</div>
                @else
                    <div class="grid grid-clips">
                        @foreach ($posts as $post)@include('site.partials.news-card', ['post' => $post, 'variant' => 'clip'])@endforeach
                    </div>
                @endif
            @elseif ($type === 1 || $posts->count() === 1)
                @include('site.partials.news-card', ['post' => $posts->first(), 'variant' => 'lead', 'full' => $type === 5])
                @if ($posts->count() > 1)
                    <div class="grid {{ $cols($posts->count() - 1, 3) }} mt">
                        @foreach ($posts->slice(1) as $post)@include('site.partials.news-card', ['post' => $post])@endforeach
                    </div>
                @endif
            @else
                <div class="grid {{ $type === 6 ? 'grid-2' : $cols($posts->count(), 3) }}">
                    @foreach ($posts as $post)@include('site.partials.news-card', ['post' => $post, 'full' => $type === 5])@endforeach
                </div>
            @endif
            @break

        @case(7)
            <div class="list">
                @foreach ($posts as $post)@include('site.partials.news-card', ['post' => $post, 'variant' => 'row', 'showDate' => $showDate ?? false])@endforeach
            </div>
            @break

        @case(8)
            @if ($posts->count() === 1)
                <div class="figure-wrap">@include('site.partials.news-card', ['post' => $posts->first(), 'variant' => 'figure'])</div>
            @else
                <div class="grid grid-clips">
                    @foreach ($posts as $post)@include('site.partials.news-card', ['post' => $post, 'variant' => 'clip'])@endforeach
                </div>
            @endif
            @break

        @case(2)
            <div class="grid {{ $posts->count() % 4 === 0 ? 'grid-4' : 'grid-3' }}">
                @foreach ($posts as $post)
                    @php
                        $img = Media::thumb($post->image, 'large') ?: Media::url($post->newspaper?->logo);
                    @endphp
                    <article class="social">
                        <a class="social-media {{ $img ? '' : 'ph' }}" href="{{ $post->tweet_url ?: route('news.show', $post->id) }}" @if ($post->tweet_url) target="_blank" rel="noopener" @endif>
                            @if ($img)<img src="{{ $img }}" alt="{{ $post->title }}" loading="lazy">@endif
                            <span class="badge-paper">{{ $platform($post->tweet_url) }}</span>
                        </a>
                        @php
                            $text = trim((string) ($post->description ?: $post->title));
                            $text = mb_strlen($text) <= 4 || preg_match('/^\S+\s*\d+$/u', $text) ? '' : $text;
                        @endphp
                        @if ($text !== '' || filled($post->tweet_url))
                            <div class="social-body">
                                @if ($text !== '')<p>{{ \Illuminate\Support\Str::words($text, 40, '…') }}</p>@endif
                                @if (filled($post->tweet_url))
                                    <a class="nc-more" href="{{ $post->tweet_url }}" target="_blank" rel="noopener">عرض المنشور <span aria-hidden="true">←</span></a>
                                @endif
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
            @break

        @case(3)
            <div class="audio-list">
                @foreach ($posts as $post)
                    @php
                        $direct = filled($post->sound_url) && preg_match('/\.(mp3|m4a|ogg|wav|aac)(\?|$)/i', $post->sound_url);
                        $sub = $post->newspaper?->name
                            ?: (($post->description && $post->description !== $post->title) ? \Illuminate\Support\Str::words($post->description, 12, '…') : null);
                    @endphp
                    <div class="audio" @if ($direct) data-audio @endif>
                        @if ($direct)
                            <button type="button" class="play" data-audio-toggle aria-label="تشغيل: {{ $post->title }}">
                                <svg class="i-play" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                <svg class="i-pause" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h4v14H7zM13 5h4v14h-4z"/></svg>
                            </button>
                            <audio preload="none" src="{{ $post->sound_url }}"></audio>
                        @elseif (filled($post->sound_url))
                            <a class="play" href="{{ $post->sound_url }}" target="_blank" rel="noopener" aria-label="استمع: {{ $post->title }}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                            </a>
                        @else
                            <span class="play is-off" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
                        @endif
                        <div class="audio-text">
                            <a class="audio-title" href="{{ route('news.show', $post->id) }}">{{ $post->title }}</a>
                            @if ($sub)<span class="audio-sub">{{ $sub }}</span>@endif
                            @if ($direct)<span class="audio-bar" aria-hidden="true"><span></span></span>@endif
                        </div>
                    </div>
                @endforeach
            </div>
            @break

        @case(4)
            @php
                $first = $posts->first();
                $ytId = $youtube($first->video_url);
            @endphp
            <div class="video {{ $embedded ? '' : 'is-solo' }}">
                @if ($ytId)
                    <button type="button" class="video-frame" data-youtube="{{ $ytId }}" aria-label="تشغيل: {{ $first->title }}"
                            style="background-image:url('https://i.ytimg.com/vi/{{ $ytId }}/hqdefault.jpg')">
                        <span class="video-play" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
                    </button>
                @else
                    <a class="video-frame {{ $first->image ? '' : 'ph-dark' }}" href="{{ $first->video_url ?: route('news.show', $first->id) }}" target="_blank" rel="noopener"
                       @if ($first->image) style="background-image:url('{{ Media::thumb($first->image, 'large') }}')" @endif>
                        <span class="video-play" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
                        <span class="sr-only">شاهد: {{ $first->title }}</span>
                    </a>
                @endif
                <div class="video-info">
                    <h3 class="video-title"><a href="{{ route('news.show', $first->id) }}">{{ $first->title }}</a></h3>
                    @if ($posts->count() > 1)
                        <ul class="video-more">
                            @foreach ($posts->slice(1) as $post)
                                <li><a href="{{ route('news.show', $post->id) }}">{{ $post->title }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            @break
    @endswitch

    @if (isset($pagination) && filled((string) $pagination))<div class="pager-wrap">{{ $pagination }}</div>@endif
@unless ($embedded)</div></section>@endunless
@endif
