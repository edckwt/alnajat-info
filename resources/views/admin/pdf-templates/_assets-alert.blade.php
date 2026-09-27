{{--
    تنبيه عند غياب ملفات صور القوالب على هذا الخادم (مثلاً بعد رفع المشروع وفيه روابط رمزية لمجلد الموقع القديم).
    $templates (اختياري): القوالب المصمَّمة المعروضة؛ وإلا تُفحص كلها.
--}}
@php
    $assetProblems = \App\Support\AssetCheck::problems();
    $missingImages = \App\Support\AssetCheck::missingTemplateImages($templates ?? null);
@endphp
@if ($assetProblems || $missingImages)
    <div class="alert alert-warning space-y-2" role="alert" data-assets-alert>
        <p class="font-extrabold">صور القوالب غير موجودة على هذا الخادم</p>
        @if ($assetProblems)
            <ul class="list-disc ps-5 text-sm space-y-0.5">
                @foreach ($assetProblems as $problem)<li dir="auto">{{ $problem }}</li>@endforeach
            </ul>
        @endif
        @if ($missingImages)
            <p class="text-sm">{{ count($missingImages) }} صورة مستخدمة في القوالب لا ملف لها:</p>
            <ul class="list-disc ps-5 text-xs space-y-0.5">
                @foreach (array_slice($missingImages, 0, 8, true) as $path => $names)
                    <li><code dir="ltr">{{ $path }}</code> — {{ implode('، ', $names) }}</li>
                @endforeach
                @if (count($missingImages) > 8)<li>و{{ count($missingImages) - 8 }} أخرى…</li>@endif
            </ul>
        @endif
        <p class="text-sm">
            الملفات (css وimages من الموقع القديم، والصور المرفوعة) لا تُنقل مع الكود. على الخادم، من مجلد المشروع:
            <code dir="ltr" class="block mt-1 font-mono">LEGACY_PATH=/مسار/الموقع/القديم {{ \App\Support\AssetCheck::FIX }}</code>
            ثم للتأكد: <code dir="ltr" class="font-mono">php artisan alnajat:assets --check</code>
        </p>
    </div>
@endif
