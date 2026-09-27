{{-- معاينة صندوق من صفحة الإعدادات بالواجهة الجديدة (المعتمدة للزوار): نفس ملف تنسيقها ونفس قالب الصندوق. --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex">
    <title>معاينة: {{ $category->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Noto+Kufi+Arabic:wght@700;800&display=swap" rel="stylesheet">
    <link href="{{ \App\Support\Theme::asset('site.css') }}" rel="stylesheet">
    <style>a { pointer-events: none; } /* المعاينة للعرض فقط */</style>
</head>
<body>
<main>
    @if ($template === null)
        <div class="wrap"><div class="empty"><h2>القالب {{ $type }} لا يُعرض في الموقع؛ اختر قالباً آخر.</h2></div></div>
    @elseif ($posts->isEmpty())
        <div class="wrap"><div class="empty"><h2>لا توجد أخبار منشورة في قسم «{{ $category->name }}» بعد.</h2></div></div>
    @else
        @include('site.partials.box', ['type' => $type, 'category' => $category, 'posts' => $posts])
    @endif
</main>
</body>
</html>
