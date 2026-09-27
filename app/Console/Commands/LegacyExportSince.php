<?php

namespace App\Console\Commands;

use App\Legacy\LegacyExporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('legacy:export-since
    {since : تاريخ الانتقال، مثل "2026-10-01 06:00"}
    {--output= : مسار ملف SQL (الافتراضي storage/app/private/legacy/rollback-….sql)}
    {--base-url=https://www.alnajat.info : بداية روابط الصور في القاعدة القديمة}
    {--deletes : قارن مع القاعدة القديمة (اتصال legacy) وصدّر المحذوفات أيضاً}')]
#[Description('خطة التراجع: تصدير ما تغير في النظام الجديد منذ الانتقال بصيغة القاعدة القديمة')]
class LegacyExportSince extends Command
{
    public function handle(): int
    {
        try {
            $since = Carbon::parse($this->argument('since'), config('app.timezone'))->format('Y-m-d H:i:s');
        } catch (Throwable) {
            $this->error('تاريخ غير صالح.');

            return self::FAILURE;
        }

        $output = $this->option('output')
            ?: storage_path('app/private/legacy/rollback-'.now()->format('Ymd-His').'.sql');
        @mkdir(dirname($output), 0775, true);

        $exporter = new LegacyExporter(
            target: DB::connection()->getPdo(),
            since: $since,
            baseUrl: (string) $this->option('base-url'),
            timezone: config('app.timezone'),
            legacy: $this->option('deletes') ? DB::connection('legacy')->getPdo() : null,
        );

        $file = fopen($output, 'w');
        foreach ($exporter->statements() as $line) {
            fwrite($file, $line."\n");
        }
        fclose($file);

        $this->table(['الجدول القديم', 'صفوف'], collect($exporter->counts)->map(fn ($n, $t) => [$t, $n])->values()->all());

        foreach ($exporter->warnings as $warning) {
            $this->warn($warning);
        }

        $this->info("الملف: {$output}");
        $this->line('طبّقه على القاعدة القديمة بعد أخذ نسخة احتياطية منها:  mysql -u… info < الملف');

        return self::SUCCESS;
    }
}
