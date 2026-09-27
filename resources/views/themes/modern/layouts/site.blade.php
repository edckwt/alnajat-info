{{--
    الواجهة الجديدة: الإطار العام (شريط علوي، ترويسة بالبحث، تنقل بالأقسام، اشتراك، تذييل).
    المتغيرات الاختيارية: $title، $description، $image، $canonical، $active (رقم القسم أو 'home' أو 'archive').
--}}
@php
    use App\Models\Setting;
    use App\Support\ArabicDate;
    use App\Support\Media;
    use App\Support\Theme;
    $siteName = Setting::get('site_title', config('app.name'));
    $slogan = Setting::get('site_slogan');
    $pageTitle = isset($title) && $title !== '' ? $title.' - '.$siteName : $siteName.($slogan ? ' - '.$slogan : '');
    $pageDescription = $description ?? Setting::get('site_description');
    $logo = Media::url(Setting::get('site_logo'));
    $pageImage = $image ?? $logo;
    $pageUrl = $canonical ?? url()->current();
    $active ??= null;
    $whatsapp = Setting::get('subscribe_whasapp');
    $email = Setting::get('subscribe_email');
    $nav = Theme::navCategories();
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl" prefix="og: http://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $pageDescription), 300) }}">
    <link rel="canonical" href="{{ $pageUrl }}">
    <meta name="theme-color" content="#0B4F6C">
    <meta property="og:locale" content="ar_AR">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $pageDescription), 300) }}">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    @if ($pageImage)<meta property="og:image" content="{{ $pageImage }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Noto+Kufi+Arabic:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="{{ Theme::asset('site.css') }}" rel="stylesheet">
    <script src="{{ Theme::asset('site.js') }}" defer></script>
    {!! Setting::get('header_code') !!}
    @stack('head')
</head>
<body>
<a class="skip" href="#main">تخطَّ إلى المحتوى</a>

<div class="topbar">
    <div class="wrap topbar-in">
        <span>{{ ArabicDate::long(now()) }}</span>
        <nav class="topbar-links" aria-label="روابط سريعة">
            <a href="{{ route('publications.index') }}">أرشيف النشرات</a>
            @if (filled($whatsapp))<a href="{{ $whatsapp }}" target="_blank" rel="noopener">اشترك عبر واتساب</a>@endif
            @if (filled($email))<a href="#subscribe-email" data-dialog-open="subscribe-email">اشترك بالبريد</a>@endif
        </nav>
    </div>
</div>

<header class="masthead">
    <div class="wrap masthead-in">
        <a class="brand" href="{{ route('home') }}">
            @if ($logo)
                <img class="brand-logo" src="{{ $logo }}" alt="" width="56" height="56">
            @else
                <span class="brand-mark" aria-hidden="true">{{ mb_substr((string) $siteName, 0, 1) }}</span>
            @endif
            <span class="brand-text">
                <span class="brand-name">{{ $siteName }}</span>
                @if (filled($slogan))<span class="brand-slogan">{{ $slogan }}</span>@endif
            </span>
        </a>
        <form class="search" role="search" method="get" action="{{ route('search') }}">
            <label for="site-search" class="sr-only">بحث في الأخبار</label>
            <input id="site-search" type="search" name="s" placeholder="ابحث في الأخبار…" value="{{ $term ?? '' }}" required>
            <button type="submit" aria-label="بحث">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </button>
        </form>
        <button class="menu-btn" type="button" aria-controls="site-nav" aria-expanded="false" data-menu>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            <span class="sr-only">القائمة</span>
        </button>
    </div>
    <nav class="wrap sitenav" id="site-nav" aria-label="الأقسام">
        <a href="{{ route('home') }}" @if ($active === 'home') aria-current="page" @endif>الرئيسية</a>
        @foreach ($nav as $category)
            <a href="{{ route('category.show', $category->id) }}" @if ($active === $category->id) aria-current="page" @endif>{{ $category->name }}</a>
        @endforeach
        <a href="{{ route('publications.index') }}" @if ($active === 'archive') aria-current="page" @endif>الأرشيف</a>
    </nav>
</header>

<main id="main">
    @yield('content')
</main>

@unless ($hideSubscribe ?? false)
    @include('site.partials.subscribe')
@endunless

<footer class="footer">
    <div class="wrap footer-in">
        <div class="footer-brand">
              @if ($logo)
                <img class="brand-logo" src="{{ $logo }}" alt="" width="56" height="56">
            @else
                <span class="brand-mark sm" aria-hidden="true">{{ mb_substr((string) $siteName, 0, 1) }}</span>
            @endif
            
            <span>{{ $siteName }}@if (filled($slogan)) — {{ $slogan }}@endif</span>
        </div>
        <nav class="footer-links" aria-label="روابط التذييل">
            <a href="{{ route('publications.index') }}">الأرشيف</a>
            <a href="{{ route('pdf.today') }}" target="_blank">نشرة اليوم PDF</a>
            @if (filled(Setting::get('unsubscribe')))<a href="{{ Setting::get('unsubscribe') }}">إلغاء الاشتراك</a>@endif
        </nav>
    </div>
</footer>

@if (filled($email))
    <dialog class="dialog" id="subscribe-email" aria-labelledby="subscribe-email-title">
        <form method="dialog" class="dialog-close-form"><button class="dialog-x" aria-label="إغلاق">×</button></form>
        <h2 id="subscribe-email-title">الاشتراك بالنشرة البريدية</h2>
        <p>تصلك نشرة كل يوم على بريدك، ويمكنك إلغاء الاشتراك في أي وقت.</p>
        <form action="https://alnajat.us4.list-manage.com/subscribe/post?u=e491488d5ec7ea051798a5ec4&amp;id=a5f6d12767" method="post" target="_blank" class="dialog-form">
            <label for="subscribe-email-input" class="sr-only">البريد الإلكتروني</label>
            <input type="email" id="subscribe-email-input" name="EMAIL" placeholder="البريد الإلكتروني" dir="ltr" required>
            <div style="position:absolute;left:-5000px" aria-hidden="true"><input type="text" name="b_e491488d5ec7ea051798a5ec4_a5f6d12767" tabindex="-1" value=""></div>
            <button type="submit" class="btn btn-primary">اشتراك</button>
        </form>
    </dialog>
@endif

{!! Setting::get('footer_code') !!}
@include('partials.theme-preview-bar')
@include('partials.impersonation-bar')
</body>
</html>
