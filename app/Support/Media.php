<?php

namespace App\Support;

/**
 * رابط ملف مخزّن في القاعدة: المسارات النسبية (upload/...) تُبنى من جذر الموقع،
 * والروابط الكاملة (القديمة أو الخارجية) تبقى كما هي.
 */
final class Media
{
    public static function url(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }

    /** المصغّر {name}_{w}x{h} من upload/thumbs إن وُجد، وإلا الأصل (منطق get_image القديمة). */
    public static function thumb(?string $path, string $size = 'medium'): ?string
    {
        if ($path === null || ! str_starts_with($path, 'upload/')) {
            return self::url($path);
        }

        [$w, $h] = config("alnajat.image_crops.$size", [350, 155]);
        $info = pathinfo($path);
        $thumb = 'upload/thumbs/'.$info['filename'].'_'.$w.'x'.$h.'.'.($info['extension'] ?? 'jpg');

        return is_file(public_path($thumb)) ? asset($thumb) : self::url($path);
    }

    public static function isImage(?string $path): bool
    {
        return $path !== null && preg_match('/\.(jpe?g|png|gif|webp|svg)(\?.*)?$/i', $path) === 1;
    }
}
