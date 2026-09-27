<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('alnajat:assets
    {--copy : نسخ المجلدات بدل الروابط الرمزية (للسيرفر)}
    {--from= : مسار الموقع القديم (الافتراضي LEGACY_PATH أو ../alnajatinfo)}')]
#[Description('ربط أو نسخ ملفات الموقع القديم (css, js, images, upload, ttfonts) إلى public/ والخطوط إلى resources/fonts')]
class LinkLegacyAssets extends Command
{
    /** المجلد في الموقع القديم => مكانه في المشروع الجديد */
    private const MAP = [
        'css' => 'public/css',
        'js' => 'public/js',
        'images' => 'public/images',
        'upload' => 'public/upload',
        'includes/custom-fonts' => 'resources/fonts',
    ];

    public function handle(): int
    {
        $from = rtrim($this->option('from') ?: config('alnajat.legacy_path'), '/');

        if (! is_dir($from)) {
            $this->error("مجلد الموقع القديم غير موجود: {$from}");

            return self::FAILURE;
        }

        foreach (self::MAP as $source => $target) {
            $src = realpath($from.'/'.$source);
            $dst = base_path($target);

            if ($src === false) {
                $this->warn("تخطي {$source}: غير موجود");

                continue;
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

        return self::SUCCESS;
    }
}
