{{-- صفحة العنوان المحظور (App\Http\Middleware\Firewall): مستقلة، بلا قاعدة بيانات ولا روابط داخل الموقع. --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>403 — تم حظر الوصول</title>
    <style>
        :root { --ink: #13232E; --muted: #5B6770; --brand: #0B4F6C; --ground: #F6F4EF; --line: #E3DED3; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: var(--ground); color: var(--ink);
               font-family: "IBM Plex Sans Arabic", Tahoma, "Segoe UI", system-ui, sans-serif; }
        main { max-width: 480px; text-align: center; background: #fff; border: 1px solid var(--line); border-radius: 24px; padding: 40px 32px; }
        .code { font-size: 64px; font-weight: 800; color: var(--brand); line-height: 1; margin: 0 0 12px; }
        h1 { font-size: 22px; margin: 0 0 10px; }
        p { color: var(--muted); line-height: 1.9; margin: 0 0 16px; }
        code { display: inline-block; direction: ltr; background: var(--ground); border: 1px solid var(--line); border-radius: 10px; padding: 4px 12px; color: var(--ink); font-size: 15px; }
    </style>
</head>
<body>
<main>
    <p class="code">403</p>
    <h1>تم حظر الوصول مؤقتاً</h1>
    <p>رُصدت من عنوانك طلبات غير معتادة فحُظر تلقائياً. إن كان هذا خطأً فتواصل معنا وأرسل عنوانك:</p>
    <code>{{ $ip }}</code>
</main>
</body>
</html>
