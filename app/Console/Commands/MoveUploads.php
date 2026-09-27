<?php

namespace App\Console\Commands;

use App\Support\Media;
use FilesystemIterator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * ينقل مجلد الصور القديم (public/upload، وقد يكون رابطاً لمجلد الموقع القديم) إلى مجلد الرفع الجديد
 * Media::root() (storage/app/public/upload). القيم في قاعدة البيانات لا تتغير (upload/…).
 *
 *   php artisan alnajat:move-uploads --dry-run     تقرير فقط: العدد والحجم والمساحة والتعارضات
 *   php artisan alnajat:move-uploads               نسخ (المصدر يبقى كما هو)، ويمكن إعادته لإكمال الناقص
 *   php artisan alnajat:move-uploads --move        نقل (يُحذف من المصدر بعد نجاح كل ملف)
 *
 * ملفات PHP و.htaccess لا تُنسخ أبداً (مجلد الرفع لا يجب أن يحوي ما يُنفَّذ).
 */
#[Signature('alnajat:move-uploads
    {--from= : المجلد المصدر (الافتراضي public/upload، وإلا upload في الموقع القديم)}
    {--move : نقل الملفات بدل نسخها}
    {--force : استبدال ملفات الوجهة المختلفة في الحجم}
    {--dry-run : تقرير بلا أي نسخ}')]
#[Description('نقل أو نسخ الصور المرفوعة من public/upload إلى storage/app/public/upload مع التحقق')]
class MoveUploads extends Command
{
    /** لا تُنقل إلى مجلد عام أبداً. */
    private const BLOCKED = '/(\.(php\d?|phtml|phar|cgi|pl|py|sh|asp|aspx|jsp)$|^\.ht)/i';

    public function handle(): int
    {
        $from = $this->source();
        $to = Media::root();
        $dry = (bool) $this->option('dry-run');

        if ($from === null) {
            $this->error('لا يوجد مجلد مصدر: public/upload ولا upload في الموقع القديم. حدّد المسار بـ --from=');

            return self::FAILURE;
        }

        $this->line("المصدر:  {$from}");
        $this->line("الوجهة:  {$to}");
        $this->line('الرابط:  /'.Media::baseUrl().'/…');

        if (realpath($to) !== false && realpath($to) === $from) {
            $this->info('المصدر هو الوجهة نفسها (رابط رمزي): لا شيء يُنقل.');

            return $this->verifyReferences($to) ? self::SUCCESS : self::FAILURE;
        }

        if ($this->option('move') && ! $dry && ! $this->confirm('سيُحذف كل ملف من المصدر بعد نسخه (والمصدر قد يكون مجلد الموقع القديم). متابعة؟', false)) {
            return self::FAILURE;
        }

        $stats = ['files' => 0, 'bytes' => 0, 'copied' => 0, 'same' => 0, 'conflicts' => 0, 'blocked' => 0, 'failed' => 0, 'need' => 0];
        $conflicts = $failed = [];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
        );

        $bar = $this->output->createProgressBar();
        $bar->setFormat(' %current% ملف [%bar%] %elapsed:6s%');
        $bar->start();

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            if (! $file->isFile()) {
                continue;
            }
            $bar->advance();

            $relative = ltrim(substr($file->getPathname(), strlen($from)), '/');
            if (preg_match(self::BLOCKED, $file->getFilename())) {
                $stats['blocked']++;

                continue;
            }

            $stats['files']++;
            $stats['bytes'] += $size = $file->getSize();
            $target = $to.'/'.$relative;

            if (is_file($target)) {
                if (filesize($target) === $size) {
                    $stats['same']++;
                    if ($this->option('move') && ! $dry) {
                        @unlink($file->getPathname());
                    }

                    continue;
                }
                if (! $this->option('force')) {
                    $stats['conflicts']++;
                    $conflicts[] = $relative;

                    continue;
                }
            }

            $stats['need'] += $size;
            if ($dry) {
                continue;
            }

            if ($this->transfer($file->getPathname(), $target, $size)) {
                $stats['copied']++;
            } else {
                $stats['failed']++;
                $failed[] = $relative;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['البند', 'العدد'], [
            ['ملفات المصدر', number_format($stats['files']).'  ('.$this->size($stats['bytes']).')'],
            [$dry ? 'ستُنسخ' : ($this->option('move') ? 'نُقلت' : 'نُسخت'), number_format($dry ? $stats['files'] - $stats['same'] - $stats['conflicts'] : $stats['copied']).'  ('.$this->size($stats['need']).')'],
            ['موجودة في الوجهة بنفس الحجم', number_format($stats['same'])],
            ['تعارض (حجم مختلف، تُترك إلا مع --force)', number_format($stats['conflicts'])],
            ['مستبعدة (PHP و.htaccess)', number_format($stats['blocked'])],
            ['فشلت', number_format($stats['failed'])],
        ]);

        foreach (array_slice($conflicts, 0, 10) as $path) {
            $this->warn("تعارض: {$path}");
        }
        foreach (array_slice($failed, 0, 10) as $path) {
            $this->error("فشل: {$path}");
        }

        if ($dry) {
            $free = @disk_free_space(is_dir($to) ? $to : dirname($to, 2));
            if ($free !== false) {
                $this->line('المساحة الحرة في الوجهة: '.$this->size((int) $free).($free < $stats['need'] ? '  ← لا تكفي!' : ''));
            }
            $this->verifyReferences($from);
            $this->info('تجربة فقط: لم يُنسخ شيء.');

            return self::SUCCESS;
        }

        // الرابط public/storage يلزم فقط إن كان مجلد الرفع داخل storage/app/public (الافتراضي)
        if (str_starts_with($to.'/', rtrim(storage_path('app/public'), '/').'/') && ! file_exists(public_path('storage'))) {
            $this->call('storage:link');
        }

        $ok = $this->verifyReferences($to) && $stats['failed'] === 0 && $stats['conflicts'] === 0;

        if ($ok) {
            $this->info('تم. بعد التأكد من الموقع يمكن حذف public/upload (أو الرابط الرمزي) لتعمل التحويلات /upload/… ← /'.Media::baseUrl().'/….');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    /** المصدر: --from، أو public/upload، أو upload في الموقع القديم (المسار الحقيقي بعد الروابط الرمزية). */
    private function source(): ?string
    {
        $candidates = $this->option('from')
            ? [str_starts_with($this->option('from'), '/') ? $this->option('from') : base_path($this->option('from'))]
            : [public_path('upload'), rtrim((string) config('alnajat.legacy_path'), '/').'/upload'];

        foreach ($candidates as $path) {
            if (($real = realpath($path)) !== false && is_dir($real)) {
                return rtrim($real, '/');
            }
        }

        return null;
    }

    private function transfer(string $source, string $target, int $size): bool
    {
        $dir = dirname($target);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return false;
        }

        $mtime = @filemtime($source);
        $tmp = $target.'.part';

        if (! @copy($source, $tmp) || filesize($tmp) !== $size || ! @rename($tmp, $target)) {
            @unlink($tmp);

            return false;
        }

        if ($mtime !== false) {
            @touch($target, $mtime);
        }
        if ($this->option('move')) {
            @unlink($source);
        }

        return true;
    }

    /**
     * تحقق من مسارات قاعدة البيانات (upload/…): كم منها ملفه موجود في المجلد.
     * الغائب هنا كان غائباً في الموقع القديم غالباً، فيُذكر ولا يُعدّ خطأ إلا إن غاب كل شيء.
     */
    private function verifyReferences(string $root): bool
    {
        $columns = [
            ['news', 'image'], ['newspapers', 'logo'], ['banners', 'image'], ['publications', 'image'],
            ['publications', 'other_file'], ['uploads', 'path'], ['users', 'avatar'],
        ];

        $rows = [];
        $total = $found = 0;
        foreach ($columns as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            $paths = DB::table($table)->where($column, 'like', Media::PREFIX.'%')->pluck($column);
            $exists = $paths->filter(fn ($p) => is_file($root.'/'.substr($p, strlen(Media::PREFIX))))->count();
            $missing = $paths->count() - $exists;
            $total += $paths->count();
            $found += $exists;
            $rows[] = ["{$table}.{$column}", number_format($paths->count()), number_format($exists), $missing ? number_format($missing) : '—'];
        }

        if ($rows === []) {
            return true;
        }

        $this->newLine();
        $this->line('مسارات قاعدة البيانات مقابل الملفات في: '.$root);
        $this->table(['الحقل', 'المسارات', 'موجودة', 'غائبة'], $rows);

        if ($total > 0 && $found === 0) {
            $this->error('لم يوجد أي ملف من مسارات قاعدة البيانات في المجلد: تأكد من المصدر قبل حذف أي شيء.');

            return false;
        }

        return true;
    }

    private function size(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, $i ? 1 : 0).' '.$units[$i];
    }
}
