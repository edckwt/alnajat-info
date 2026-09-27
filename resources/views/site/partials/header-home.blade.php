@php
    use App\Models\Setting;
    use App\Support\Media;
@endphp
@if ($publication?->cover && $publication?->image)
    @push('head')<style>.cover{ background: url({{ Media::url($publication->image) }}) no-repeat top center #fff !important; }</style>@endpush
@endif
<header class="cover">
    <div class="row">
        <div class="logo"><a href="{{ route('home') }}"><img src="{{ Media::url(Setting::get('site_logo')) }}" alt="{{ Setting::get('site_title') }}" title="{{ Setting::get('site_title') }}"></a></div>
    </div>
    <div class="row">
        <div class="title">
            <h1>{{ Setting::get('site_title') }}</h1>
            <h2>{{ Setting::get('site_slogan') }}</h2>
        </div>
    </div>
    <div class="row">
        <div class="date"><p>{{ \App\Support\ArabicDate::long(now()) }}</p></div>
    </div>
</header>
