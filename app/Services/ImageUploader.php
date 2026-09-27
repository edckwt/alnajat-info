<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * يحفظ الصور بنفس أسلوب النظام القديم (upload_files في includes/function.php)
 * حتى يبقى الموقع العام والـ PDF يجدان الصور ومصغّراتها في نفس الأماكن:
 *
 *   public/upload/{prefix}_{time}_{random15}.{ext}                الأصل
 *   public/upload/thumbs/{name}_{w}x{h}.{ext}                    قصّ لكل مقاس في crop()
 *   public/upload/{name}_thumbnail.{ext}                         عرض 200 بنفس النسبة
 *
 * ويعيد المسار النسبي 'upload/...' الذي يُحفظ في قاعدة البيانات.
 */
class ImageUploader
{
    public function __construct(private readonly ?string $root = null) {}

    public function store(UploadedFile $file, string $prefix = 'news'): string
    {
        $root = $this->root ?? public_path('upload');
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $name = $prefix.'_'.time().'_'.Str::random(15);

        $file->move($root, $name.'.'.$extension);

        $this->makeThumbnails($root, $name, $extension);

        return 'upload/'.$name.'.'.$extension;
    }

    private function makeThumbnails(string $root, string $name, string $extension): void
    {
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return;
        }

        $source = $root.'/'.$name.'.'.$extension;

        if (! is_dir($root.'/thumbs')) {
            mkdir($root.'/thumbs', 0755, true);
        }

        try {
            $manager = ImageManager::usingDriver(Driver::class);

            foreach (config('alnajat.image_crops', []) as [$width, $height]) {
                $manager->decodePath($source)
                    ->orient()
                    ->cover($width, $height)
                    ->save($root.'/thumbs/'.$name.'_'.$width.'x'.$height.'.'.$extension);
            }

            $manager->decodePath($source)
                ->orient()
                ->scaleDown(width: (int) config('alnajat.image_thumbnail_width', 200))
                ->save($root.'/'.$name.'_thumbnail.'.$extension);
        } catch (Throwable $e) {
            // الصورة الأصلية محفوظة؛ غياب المصغّرات لا يمنع الحفظ
            // (get_image القديمة تعود للأصل إن لم تجد المصغّر).
            report($e);
        }
    }
}
