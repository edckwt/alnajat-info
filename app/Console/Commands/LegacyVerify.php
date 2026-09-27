<?php

namespace App\Console\Commands;

use App\Legacy\LegacyTransform;
use App\Legacy\LegacyVerifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('legacy:verify')]
#[Description('مقارنة القاعدة الجديدة بالقديمة صفاً بصف بعد legacy:import')]
class LegacyVerify extends Command
{
    public function handle(LegacyTransform $transform): int
    {
        $verifier = new LegacyVerifier(
            source: DB::connection('legacy')->getPdo(),
            target: DB::connection()->getPdo(),
            t: $transform,
            homeBoxes: (int) config('alnajat.home_boxes', 15),
        );

        foreach ($verifier->run() as $result) {
            if ($result['ok']) {
                $this->line("<info>✓</info> {$result['check']} — {$result['detail']}");
            } else {
                $this->line("<error>✗</error> {$result['check']}");
                foreach (explode("\n", $result['detail']) as $line) {
                    $this->line("    {$line}");
                }
            }
        }

        $this->newLine();

        if (! $verifier->passed()) {
            $this->error('توجد فروقات. لا تكمل الإطلاق قبل حلّها.');

            return self::FAILURE;
        }

        $this->info('القاعدتان متطابقتان.');

        return self::SUCCESS;
    }
}
