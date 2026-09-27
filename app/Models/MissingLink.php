<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * رابط أعاد 404. صف واحد لكل رابط مع عدد مرات طلبه وآخر صفحة أحالت إليه،
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
        $now = now();

        try {
            DB::table('missing_links')->upsert(
                [['path' => $path, 'path_hash' => sha1($path), 'referer' => $referer, 'hits' => 1, 'created_at' => $now, 'updated_at' => $now]],
                ['path_hash'],
                ['hits' => DB::raw('hits + 1'), 'updated_at' => $now, 'referer' => $referer],
            );
        } catch (Throwable) {
            // التسجيل لا يجوز أن يكسر صفحة 404 نفسها (مثلاً قبل تشغيل migrate).
        }
    }
}
