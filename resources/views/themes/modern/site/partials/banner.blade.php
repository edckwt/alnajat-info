<section class="band band-tight">
    <div class="wrap">
        <div class="banner">
            @if ($banner->image)
                <a href="{{ $banner->url ?: '#' }}" target="_blank" rel="noopener"><img src="{{ \App\Support\Media::url($banner->image) }}" alt="{{ $banner->title }}" loading="lazy"></a>
            @endif
            @if (filled($banner->description))<p class="banner-desc">{{ $banner->description }}</p>@endif
            @if (filled($banner->body))<div class="prose">{!! $banner->body !!}</div>@endif
        </div>
    </div>
</section>
