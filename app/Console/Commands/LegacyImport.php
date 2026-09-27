<?php

namespace App\Console\Commands;

use App\Legacy\LegacyImporter;
use App\Legacy\LegacyTransform;
use App\Services\PdfCache;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('legacy:import
    {--fresh : تفريغ جداول القاعدة الجديدة قبل النقل (مطلوب إن كان فيها بيانات)}
    {--force : التشغيل على production بدون سؤال}')]
#[Description('نقل بيانات موقع النجاة القديم (اتصال legacy) إلى القاعدة الجديدة')]
class LegacyImport extends Command
{
    public function handle(LegacyTransform $transform): int
    {
        if ($this->option('fresh') && $this->laravel->isProduction() && ! $this->option('force')
            && ! $this->confirm('سيُحذف كل محتوى القاعدة الجديدة ثم يُنقل من القديمة. متابعة؟')) {
            return self::FAILURE;
        }

        $importer = new LegacyImporter(
            source: DB::connection('legacy')->getPdo(),
            target: DB::connection()->getPdo(),
            t: $transform,
            homeBoxes: (int) config('alnajat.home_boxes', 15),
            log: fn (string $line) => $this->line($line),
        );

        $started = microtime(true);

        try {
            $report = $importer->run(fresh: (bool) $this->option('fresh'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        Cache::forget('settings.all');
        app(PdfCache::class)->flush(); // ملفات PDF المولدة قبل الاستيراد لم تعد صالحة

        $this->newLine();
        $this->table(
            ['الجدول الجديد', 'المصدر', 'قُرئ', 'كُتب', 'تُخطي'],
            collect($report->tables)->map(fn ($t, $name) => [
                $name, $t['source'], $t['read'], $t['written'], $report->skippedCount($name),
            ])->values()->all(),
        );

        foreach ($report->skipped as $s) {
            $this->warn("تخطي {$s['table']}#{$s['id']}: {$s['reason']}");
        }
        foreach ($report->notes as $n) {
            $this->line("<comment>ملاحظة</comment> {$n['table']}#{$n['id']}: {$n['reason']}");
        }

        $path = 'legacy/import-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->newLine();
        $this->info(sprintf('انتهى النقل في %.1f ثانية. التقرير: storage/app/private/%s', microtime(true) - $started, $path));
        $this->line('الخطوة التالية: php artisan legacy:verify');

        return self::SUCCESS;
    }
}
