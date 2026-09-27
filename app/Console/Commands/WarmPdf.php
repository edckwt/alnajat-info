<?php

namespace App\Console\Commands;

use App\Jobs\GeneratePublicationPdf;
use App\Models\Publication;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pdf:warm {--limit=1 : عدد آخر النشرات المنشورة} {--queue : عبر الطابور بدلاً من التنفيذ الآن}')]
#[Description('تجهيز ملفات PDF لآخر النشرات مسبقاً')]
class WarmPdf extends Command
{
    public function handle(): int
    {
        $ids = Publication::published()->latest('id')->limit(max(1, (int) $this->option('limit')))->pluck('id');

        foreach ($ids as $id) {
            if ($this->option('queue')) {
                GeneratePublicationPdf::dispatch($id);
                $this->line("queued #{$id}");

                continue;
            }

            $started = microtime(true);
            GeneratePublicationPdf::dispatchSync($id);
            $this->line(sprintf('#%d  %.1fs', $id, microtime(true) - $started));
        }

        $this->info("تم: {$ids->count()} نشرة.");

        return self::SUCCESS;
    }
}
