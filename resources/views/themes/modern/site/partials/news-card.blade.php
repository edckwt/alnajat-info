{{--
    بطاقة خبر. $post، $variant: card | lead | row | clip | figure | mini، $full (الوصف كاملاً)، $showDate.
    خبر بلا نص حقيقي (قصاصة أو إنفوجرافيك عنوانه «خبر» أو «تغريدة 1») يُعرض كصورة: card → clip، lead → figure.
--}}
@php
    use App\Support\ArabicDate;
    use App\Support\Media;
    use Illuminate\Support\Str;
    $variant ??= 'card';
    $full ??= false;
    $title = trim((string) $post->title);
    $weakTitle = mb_strlen($title) <= 4 || preg_match('/^\S+\s*\d+$/u', $title) === 1;
    $desc = trim((string) $post->description);
    $desc = $desc === $title ? '' : $desc;
    if ($post->image && $desc === '' && $weakTitle) {
        $variant = match ($variant) { 'card' => 'clip', 'lead' => 'figure', default => $variant };
    }
    $showDate ??= false;
    $url = route('news.show', $post->id);
    $paper = $post->newspaper?->name;
    $isProject = (int) $post->type === 5 && filled($post->source_url);
    $image = match ($variant) {
        'mini' => Media::thumb($post->image, 'xsmall'),
        'clip', 'figure' => Media::url($post->image),
        default => Media::thumb($post->image, 'large'),
    };
    $excerpt = $full ? $desc : Str::words($desc, $variant === 'lead' ? 60 : 28, '…');
@endphp
<article class="nc nc-{{ $variant }}">
    @if ($variant === 'figure')
        <a class="nc-figure" href="{{ $url }}">
            @if ($image)<img src="{{ $image }}" alt="{{ $weakTitle ? ($paper ?: $title) : $title }}" loading="lazy">@endif
        </a>
        @if (! $weakTitle || $paper)
            <div class="nc-body nc-caption">
                @unless ($weakTitle)<h3 class="nc-title"><a href="{{ $url }}">{{ $title }}</a></h3>@endunless
                @if ($paper)<span class="nc-meta">{{ $paper }}</span>@endif
            </div>
        @endif
    @elseif ($variant === 'mini')
        <a class="nc-mini-link" href="{{ $url }}">
            @if ($image)<img src="{{ $image }}" alt="" loading="lazy" width="64" height="64">@else<span class="nc-mini-ph ph" aria-hidden="true"></span>@endif
            <span class="nc-mini-text"><span class="nc-mini-title">{{ $post->title }}</span>@if ($paper)<span class="nc-meta">{{ $paper }}</span>@endif</span>
        </a>
    @else
        <a class="nc-media {{ $image ? '' : 'ph' }}" href="{{ $url }}" tabindex="-1" aria-hidden="true">
            @if ($image)<img src="{{ $image }}" alt="" loading="lazy">@endif
            @if ($paper && $variant === 'clip')<span class="badge-paper">{{ $paper }}</span>@endif
        </a>
        <div class="nc-body">
            @if ($variant !== 'clip' && ($paper || $showDate))
                <div class="nc-meta">
                    @if ($paper)<span>{{ $paper }}</span>@endif
                    @if ($showDate && $post->published_date)<span>{{ ArabicDate::long($post->published_date) }}</span>@endif
                </div>
            @endif
            @if (! ($weakTitle && $variant === 'clip'))
                <h3 class="nc-title"><a href="{{ $url }}">{{ $title }}</a></h3>
            @endif
            @if ($variant !== 'clip' && $excerpt !== '')
                <p class="nc-excerpt">{{ $excerpt }}</p>
            @endif
            @if ($isProject)
                <a class="btn btn-amber btn-sm" href="{{ $post->source_url }}" target="_blank" rel="noopener">تبرع الآن</a>
            @elseif ($variant === 'clip' && filled($post->source_url))
                <a class="nc-more" href="{{ $post->source_url }}" target="_blank" rel="noopener">طالع المصدر <span aria-hidden="true">←</span></a>
            @elseif ($variant === 'lead' || $variant === 'row')
                <a class="nc-more" href="{{ $url }}">أكمل القراءة <span aria-hidden="true">←</span></a>
            @endif
        </div>
    @endif
</article>
