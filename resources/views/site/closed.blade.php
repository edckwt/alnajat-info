<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <meta charset="UTF-8">
    <title>{{ \App\Models\Setting::get('site_title', config('app.name')) }}</title>
    <link href="{{ asset('css/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
</head>
<body>
<main>
    <div class="container mt-5">
        <div class="card bg-light cpanel-menu">
            <div class="card-header"><h3 class="card-title">الموقع مغلق</h3></div>
            <div class="card-body">{!! nl2br(e($cause)) !!}</div>
        </div>
    </div>
</main>
</body>
</html>
