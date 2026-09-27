<div class="banner_content">
    <a href="{{ $banner->url }}" target="_blank"><img src="{{ \App\Support\Media::url($banner->image) }}" alt="{{ $banner->title }}" class="w-100 mt-3"></a>
    @if (filled($banner->description))<p>{{ $banner->description }}</p>@endif
    {!! $banner->body !!}
</div>
