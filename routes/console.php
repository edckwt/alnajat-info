<?php

use Illuminate\Support\Facades\Schedule;

// نشرة اليوم جاهزة دائماً (يُعاد توليدها فقط إن تغيّرت أو مضت مدة today_ttl).
Schedule::command('pdf:warm --limit=1')
    ->everyFiveMinutes()
    ->withoutOverlapping(30)
    ->runInBackground();

// تنظيف ملفات mPDF المؤقتة وملفات .tmp المتروكة.
Schedule::call(function () {
    foreach ([storage_path('app/mpdf'), storage_path('app/pdf')] as $dir) {
        foreach (glob($dir.'/{,*/}*.tmp', GLOB_BRACE) ?: [] as $file) {
            if (filemtime($file) < time() - 3600) {
                @unlink($file);
            }
        }
    }
})->daily()->name('pdf:cleanup');

// روابط 404 التي طُلبت مرة أو مرتين قبل أكثر من شهر (زحف عشوائي) تُحذف.
Schedule::call(fn () => \App\Models\MissingLink::where('hits', '<', 3)->where('updated_at', '<', now()->subDays(30))->delete())
    ->daily()->name('missing-links:prune');
