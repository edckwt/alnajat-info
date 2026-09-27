@php
    use App\Models\Setting;
    use App\Support\Media;
    $siteName = Setting::get('site_title', config('app.name'));
    $pageTitle = isset($title) && $title !== '' ? $title.' - '.$siteName : $siteName.(Setting::get('site_slogan') ? ' - '.Setting::get('site_slogan') : '');
    $pageDescription = $description ?? Setting::get('site_description');
    $pageImage = $image ?? Media::url(Setting::get('site_logo'));
    $pageUrl = $canonical ?? url()->current();
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl" prefix="og: http://ogp.me/ns#">
<head>
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <meta charset="UTF-8">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ strip_tags((string) $pageDescription) }}">
    <link rel="canonical" href="{{ $pageUrl }}">
    <meta property="og:locale" content="ar_AR">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ strip_tags((string) $pageDescription) }}">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    @if ($pageImage)<meta property="og:image" content="{{ $pageImage }}">@endif
    <link href="{{ asset('css/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/fontawesome/all.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-3.3.1.slim.min.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}"></script>
    {!! Setting::get('header_code') !!}
    @stack('head')
</head>
<body>
<main class="mob-view col-12">
    @yield('header')

    @yield('content')

    @include('site.partials.subscribe')
</main>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
{!! Setting::get('footer_code') !!}
@include('partials.theme-preview-bar')
</body>
</html>
