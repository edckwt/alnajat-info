{{-- صفحات الأخطاء بالعربية (بدل صفحات Laravel الإنجليزية)، مستقلة عن أي ملف تنسيق. --}}
@php
    $inPanel = request()->is('cp', 'cp/*');
    $home = $inPanel ? url('cp') : url('/');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') — @yield('title')</title>
    <style>
        :root { --ink: #13232E; --muted: #5B6770; --brand: #0B4F6C; --ground: #F6F4EF; --line: #E3DED3; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px;
               background: var(--ground); color: var(--ink);
               font-family: "IBM Plex Sans Arabic", Tahoma, "Segoe UI", system-ui, sans-serif; }
        main { max-width: 480px; text-align: center; background: #fff; border: 1px solid var(--line);
               border-radius: 24px; padding: 40px 32px; }
        .code { font-size: 64px; font-weight: 800; color: var(--brand); line-height: 1; margin: 0 0 12px; }
        h1 { font-size: 22px; margin: 0 0 10px; }
        p { color: var(--muted); line-height: 1.9; margin: 0 0 24px; }
        a { display: inline-block; background: var(--brand); color: #fff; text-decoration: none;
            font-weight: 700; padding: 12px 24px; border-radius: 12px; }
        a:focus-visible { outline: 3px solid #E09F3E; outline-offset: 2px; }
    </style>
</head>
<body>
<main>
    <p class="code">@yield('code')</p>
    <h1>@yield('title')</h1>
    <p>@yield('message')</p>
    <a href="{{ $home }}">{{ $inPanel ? 'العودة إلى لوحة التحكم' : 'العودة إلى الرئيسية' }}</a>
</main>
</body>
</html>
