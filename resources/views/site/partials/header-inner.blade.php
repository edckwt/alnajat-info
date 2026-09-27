<header class="inner-header">
    <div class="row">
        <div class="inner-logo"><a href="{{ route('home') }}"><img src="{{ \App\Support\Media::url(\App\Models\Setting::get('site_logo')) }}" alt="{{ \App\Models\Setting::get('site_title') }}" title="{{ \App\Models\Setting::get('site_title') }}"></a></div>
        <div class="inner-date"><p>{{ \App\Support\ArabicDate::long(now()) }}</p></div>
    </div>
</header>
