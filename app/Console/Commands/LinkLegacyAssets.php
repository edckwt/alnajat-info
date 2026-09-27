<?php

namespace App\Console\Commands;

use App\Support\AssetCheck;
use App\Support\Media;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * ملفات الموقع القديم التي لا تُرفع مع الكود.
 *
 *   php artisan alnajat:assets            روابط رمزية (محلياً)
 *   php artisan alnajat:assets --copy     نسخ حقيقية (الخادم): تستبدل الروابط الرمزية، ومنها المكسورة
 *                                         (مشروع رُفع من الجهاز المحلي فيه روابط إلى /Applications/MAMP/…)
 *   php artisan alnajat:assets --check    تقرير فقط: الناقص والروابط المكسورة وصور القوالب المفقودة
 */
#[Signature('alnajat:assets
    {--copy : نسخ المجلدات بدل الروابط الرمزية (للخادم)}
    {--check : فحص فقط بلا أي تغيير}
    {--from= : مسار الموقع القديم (الافتراضي LEGACY_PATH أو ../alnajatinfo)}')]
#[Description('ربط أو نسخ ملفات الموقع القديم (css, js, images, الخطوط) والصور المرفوعة، أو فحصها بـ --check')]
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
        if ($this->option('check')) {
            return $this->check();
        }

        $from = rtrim($this->option('from') ?: config('alnajat.legacy_path'), '/');

        if (! is_dir($from)) {
            $this->error("مجلد الموقع القديم غير موجود: {$from}");
            $this->line('حدّده بـ --from=/مسار/الموقع/القديم أو LEGACY_PATH في .env');

            return self::FAILURE;
        }

        $status = self::SUCCESS;
        $copy = (bool) $this->option('copy');

        foreach (self::MAP as $source => $target) {
            $src = realpath($from.'/'.$source);
            $dst = $target === null ? Media::root() : base_path($target);
            $target ??= ltrim(str_replace(base_path(), '', $dst), '/');

            if ($src === false) {
                $this->warn("تخطي {$source}: غير موجود في {$from}");

                continue;
            }

            // رابط رمزي مكسور (مثلاً إلى مجلد على الجهاز المحلي): يُحذف ويُعاد إنشاؤه.
            // ومع --copy يُستبدل أي رابط رمزي بنسخة حقيقية حتى لا يعتمد الموقع على مجلد آخر.
            if (is_link($dst) && (! file_exists($dst) || $copy)) {
                $this->line((file_exists($dst) ? 'استبدال الرابط' : 'حذف الرابط المكسور')." {$target} ← ".readlink($dst));
                @unlink($dst);
            }

            if (! is_dir(dirname($dst))) {
                File::makeDirectory(dirname($dst), 0755, true);
            }

            // الصور بالنسخ: عبر alnajat:move-uploads (يستبعد ملفات PHP، ويكمل الناقص، ويتحقق)
            if ($source === 'upload' && $copy) {
                if ($this->call('alnajat:move-uploads', ['--from' => $src]) !== self::SUCCESS) {
                    $status = self::FAILURE;
                }

                continue;
            }

            // مع --copy ومجلد حقيقي موجود: يُكمل النسخ فوقه (لالتقاط آخر الصور يوم الانتقال).
            if (is_link($dst) || (file_exists($dst) && ! ($copy && is_dir($dst)))) {
                $this->line("موجود مسبقاً: {$target}");

                continue;
            }

            if ($copy) {
                File::copyDirectory($src, $dst);
                $this->info("نُسخ {$source} ← {$target}");
            } else {
                File::link($src, $dst);
                $this->info("رابط {$target} ← {$src}");
            }
        }

        $storage = rtrim(storage_path('app/public'), '/').'/';
        if (str_starts_with(Media::root().'/', $storage)) {
            if (is_link(public_path('storage')) && ! file_exists(public_path('storage'))) {
                @unlink(public_path('storage'));
            }
            if (! file_exists(public_path('storage'))) {
                $this->call('storage:link');
            }
        }

        $this->newLine();

        return $this->check() === self::SUCCESS ? $status : self::FAILURE;
    }

    private function check(): int
    {
        $problems = AssetCheck::problems();
        $missing = AssetCheck::missingTemplateImages();

        foreach ($problems as $problem) {
            $this->error($problem);
        }

        if ($missing) {
            $this->warn(count($missing).' صورة مستخدمة في القوالب المصمَّمة لا ملف لها:');
            foreach (array_slice($missing, 0, 20, true) as $path => $names) {
                $this->line("  {$path}  ← ".implode('، ', $names));
            }
        }

        if ($problems || $missing) {
            $this->line('الحل على الخادم: LEGACY_PATH=/مسار/الموقع/القديم '.AssetCheck::FIX);

            return self::FAILURE;
        }

        $this->info('كل ملفات الموقع وصور القوالب موجودة.');

        return self::SUCCESS;
    }
}
