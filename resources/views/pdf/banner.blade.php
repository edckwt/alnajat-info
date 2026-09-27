<div class="banner_content">
    <a href="{{ $banner->url }}"><img src="{{ \App\Services\PdfBuilder::src($banner->image) }}" alt="{{ $banner->title }}"></a>
    @if (filled($banner->description))<p>{{ $banner->description }}</p>@endif
    {!! $banner->body !!}
</div>
