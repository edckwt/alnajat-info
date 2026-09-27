{{-- معاينة صندوق من صفحة الإعدادات: نفس ملفات تنسيق الموقع العام ونفس قالب الصندوق. --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex">
    <title>معاينة: {{ $category->name }}</title>
    <link href="{{ asset('css/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/fontawesome/all.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-3.3.1.slim.min.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}"></script>
    <style>
        .preview-empty { padding: 4rem 1rem; text-align: center; color: #64748b; font-size: 1.05rem; }
        a { pointer-events: none; } /* المعاينة للعرض فقط */
    </style>
</head>
<body>
<main class="mob-view col-12">
    @if ($template === null)
        <p class="preview-empty">القالب {{ $type }} لا يُعرض في الموقع؛ اختر قالباً آخر.</p>
    @elseif ($posts->isEmpty())
        <p class="preview-empty">لا توجد أخبار منشورة في قسم «{{ $category->name }}» بعد.</p>
    @else
        @include('site.partials.box', ['type' => $type, 'category' => $category, 'posts' => $posts])
    @endif
</main>
</body>
</html>
