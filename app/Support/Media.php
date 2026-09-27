<?php

namespace App\Support;

/**
 * رابط ملف مخزّن في القاعدة، ومكانه على القرص.
 *
 *  - «upload/…» (الصور والملفات المرفوعة): تبقى القيمة في القاعدة كما في الموقع القديم،
 *    والملف في alnajat.uploads.root (storage/app/public/upload) ورابطه /storage/upload/…
 *  - مسارات نسبية أخرى (images/…): من جذر الموقع public/.
 *  - الروابط الكاملة (القديمة أو الخارجية): كما هي.
 */
final class Media
{
    public const PREFIX = 'upload/';

    public static function url(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        if (($relative = self::relative($path)) !== null) {
            return asset(self::baseUrl().($relative === '' ? '' : '/'.$relative));
        }

        return asset(ltrim($path, '/'));
    }

    /** المصغّر {name}_{w}x{h} من upload/thumbs إن وُجد، وإلا الأصل (منطق get_image القديمة). */
    public static function thumb(?string $path, string $size = 'medium'): ?string
    {
        if (! self::isUpload($path)) {
            return self::url($path);
        }

        [$w, $h] = config("alnajat.image_crops.$size", [350, 155]);
        $info = pathinfo(ltrim($path, '/'));
        $thumb = self::PREFIX.'thumbs/'.$info['filename'].'_'.$w.'x'.$h.'.'.($info['extension'] ?? 'jpg');

        return is_file((string) self::path($thumb)) ? self::url($thumb) : self::url($path);
    }

    /** هل المسار من الملفات المرفوعة (upload/…)؟ */
    public static function isUpload(?string $path): bool
    {
        return self::relative($path) !== null;
    }

    /**
     * مكان ملف «upload/…» على القرص (موجوداً أو لا)، أو null لغير المرفوعات.
     * لا يقبل «..» حتى لا يخرج أحد عن مجلد الرفع (مثلاً عند الحذف).
     */
    public static function path(?string $path): ?string
    {
        $relative = self::relative($path);
        if ($relative === null || $relative === '' || preg_match('#(^|/)\.\.(/|$)#', $relative)) {
            return null;
        }

        return self::root().'/'.$relative;
    }

    /** مجلد الرفع على القرص (الافتراضي storage/app/public/upload). */
    public static function root(): string
    {
        return rtrim((string) config('alnajat.uploads.root', storage_path('app/public/upload')), '/');
    }

    /** مسار مجلد الرفع في الموقع بلا «/» في طرفيه (الافتراضي storage/upload). */
    public static function baseUrl(): string
    {
        return trim((string) config('alnajat.uploads.url', 'storage/upload'), '/');
    }

    public static function isImage(?string $path): bool
    {
        return $path !== null && preg_match('/\.(jpe?g|png|gif|webp|svg)(\?.*)?$/i', $path) === 1;
    }

    /** الجزء بعد «upload/» أو null إن لم يكن من المرفوعات. */
    private static function relative(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $path = ltrim(trim($path), '/');

        return str_starts_with($path, self::PREFIX) ? substr($path, strlen(self::PREFIX)) : null;
    }
}
