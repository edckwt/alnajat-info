<?php

namespace App\Console\Commands;

use App\Models\Publication;
use App\Services\PdfBuilder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

#[Signature('pdf:samples
    {--old= : عنوان الموقع القديم لتنزيل نفس النشرة منه للمقارنة، مثل http://localhost:8888/alnajatinfo}
    {--id=* : نشرات محددة بدل آخر نشرة من كل نسخة}')]
#[Description('نشرة من كل نسخة قالب (v1 إلى v5) من النظامين جنباً إلى جنب للمطابقة البصرية')]
class PdfSamples extends Command
{
    public function handle(PdfBuilder $builder): int
    {
        @set_time_limit(0);

        $publications = $this->option('id')
            ? Publication::whereIn('id', $this->option('id'))->get()
            : collect(range(1, (int) config('alnajat.pdf_latest_version')))
                ->map(fn (int $v) => Publication::published()->where('pdf_version', $v)->latest('id')->first())
                ->filter();

        $dir = storage_path('app/private/pdf-samples');
        @mkdir($dir, 0775, true);

        $rows = [];
        foreach ($publications as $publication) {
            $name = "v{$publication->pdf_version}-{$publication->id}";
            $started = microtime(true);

            file_put_contents("{$dir}/{$name}-new.pdf", $builder->publication($publication));
            $row = [$publication->pdf_version, $publication->id, $publication->publication_date?->toDateString(), sprintf('%.1fs', microtime(true) - $started), "{$name}-new.pdf", '—'];

            if ($old = $this->option('old')) {
                $row[5] = $this->downloadOld(rtrim($old, '/'), $publication->id, "{$dir}/{$name}-old.pdf");
            }

            $rows[] = $row;
        }

        $this->table(['النسخة', 'النشرة', 'التاريخ', 'التوليد', 'الجديد', 'القديم'], $rows);
        $this->info("الملفات في {$dir}");
        $this->line('افتح كل زوج جنباً إلى جنب: الغلاف، ترتيب الأقسام، الخطوط، الصور، أرقام الصفحات.');

        return self::SUCCESS;
    }

    private function downloadOld(string $base, int $id, string $path): string
    {
        try {
            $response = Http::timeout(600)->get($base.'/index.php', ['read' => 'pdf', 'online' => 1, 'publication_id' => $id]);
            $body = $response->body();

            if (! str_starts_with($body, '%PDF')) {
                return 'ليس PDF (HTTP '.$response->status().')';
            }

            file_put_contents($path, $body);

            return basename($path);
        } catch (Throwable $e) {
            return 'تعذّر: '.mb_substr($e->getMessage(), 0, 60);
        }
    }
}
