<?php

namespace App\Console\Commands;

use App\Support\Media;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('alnajat:assets
    {--copy : نسخ المجلدات بدل الروابط الرمزية (للسيرفر)}
    {--from= : مسار الموقع القديم (الافتراضي LEGACY_PATH أو ../alnajatinfo)}')]
#[Description('ربط أو نسخ ملفات الموقع القديم (css, js, images, ttfonts) إلى public/، والصور upload إلى مجلد الرفع (storage/app/public/upload)، والخطوط إلى resources/fonts')]
class LinkLegacyAssets extends Command
{
    /** المجلد في الموقع القديم => مكانه في المشروع الجديد (upload: مجلد الرفع Media::root()) */
    private const MAP = [
        'css' => 'public/css',
        'js' => 'public/js',
        'images' => 'public/images',
        'upload' => null,
        'includes/custom-fonts' => 'resources/fonts',
    ];

    public function handle(): int
    {
        $from = rtrim($this->option('from') ?: config('alnajat.legacy_path'), '/');

        if (! is_dir($from)) {
            $this->error("مجلد الموقع القديم غير موجود: {$from}");

            return self::FAILURE;
        }

        $status = self::SUCCESS;

        foreach (self::MAP as $source => $target) {
            $src = realpath($from.'/'.$source);
            $dst = $target === null ? Media::root() : base_path($target);
            $target ??= ltrim(str_replace(base_path(), '', $dst), '/');

            if ($src === false) {
                $this->warn("تخطي {$source}: غير موجود");

                continue;
            }

            // الصور بالنسخ: عبر alnajat:move-uploads (يستبعد ملفات PHP، ويكمل الناقص، ويتحقق)
            if ($source === 'upload' && $this->option('copy')) {
                if (is_link($dst)) {
                    $this->line("موجود مسبقاً (رابط): {$target}");

                    continue;
                }
                if ($this->call('alnajat:move-uploads', ['--from' => $src]) !== self::SUCCESS) {
                    $status = self::FAILURE;
                }

                continue;
            }

            if (! is_dir(dirname($dst))) {
                File::makeDirectory(dirname($dst), 0755, true);
            }

            // مع --copy ومجلد حقيقي موجود: يُكمل النسخ فوقه (لالتقاط آخر الصور يوم الانتقال).
            if (is_link($dst) || (file_exists($dst) && ! ($this->option('copy') && is_dir($dst)))) {
                $this->line("موجود مسبقاً: {$target}");

                continue;
            }

            if ($this->option('copy')) {
                File::copyDirectory($src, $dst);
                $this->info("نُسخ {$source} ← {$target}");
            } else {
                File::link($src, $dst);
                $this->info("رابط {$target} ← {$src}");
            }
        }

        if (str_starts_with(Media::root().'/', rtrim(storage_path('app/public'), '/').'/') && ! file_exists(public_path('storage'))) {
            $this->call('storage:link');
        }

        return $status;
    }
}
