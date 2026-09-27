@php($siteName = \App\Models\Setting::get('site_title', config('app.name')))
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $siteName }}</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600&family=Noto+Kufi+Arabic:wght@800&display=swap" rel="stylesheet">
    <link href="{{ \App\Support\Theme::asset('site.css') }}" rel="stylesheet">
</head>
<body class="closed">
<main class="closed-card">
    <span class="brand-mark" aria-hidden="true">{{ mb_substr((string) $siteName, 0, 1) }}</span>
    <h1>{{ $siteName }}</h1>
    <p class="muted">الموقع متوقف مؤقتاً</p>
    @if (filled($cause))<div class="closed-cause">{!! nl2br(e($cause)) !!}</div>@endif
</main>
</body>
</html>
