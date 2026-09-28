<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * رابط أعاد 404. صف واحد لكل رابط مع عدد مرات طلبه وآخر صفحة أحالت إليه وعنوان IP لآخر من طلبه،
 * حتى تظهر الروابط القديمة المكسورة بعد الانتقال مرتبة بالأهمية.
 */
class MissingLink extends Model
{
    public static function record(Request $request): void
    {
        if (! $request->isMethod('GET') || $request->is('cp', 'cp/*')) {
            return;
        }

        $path = Str::limit(rawurldecode($request->getRequestUri()), 990, '');
        $referer = $request->headers->get('referer');
        $referer = $referer ? Str::limit($referer, 990, '') : null;
        $ip = $request->ip(); // خلف وكيل (Cloudflare…) يلزم ضبط TrustProxies ليكون IP الزائر لا الوكيل
        $now = now();

        $hash = sha1($path);
        $changes = ['hits' => DB::raw('hits + 1'), 'updated_at' => $now, 'referer' => $referer, 'ip' => $ip];

        // تحديث ثم إضافة (بدل upsert: لا تدعمه نسخ SQLite القديمة قبل 3.24، ومنها SQLite الاختبارات على الخادم)
        try {
            if (DB::table('missing_links')->where('path_hash', $hash)->update($changes) === 0) {
                try {
                    DB::table('missing_links')->insert([
                        'path' => $path, 'path_hash' => $hash, 'referer' => $referer, 'ip' => $ip,
                        'hits' => 1, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    DB::table('missing_links')->where('path_hash', $hash)->update($changes); // طلبان متزامنان
                }
            }
        } catch (Throwable $e) {
            // التسجيل لا يجوز أن يكسر صفحة 404 نفسها (مثلاً قبل تشغيل migrate)، لكن يُذكر في السجل.
            report($e);
        }
    }
}
