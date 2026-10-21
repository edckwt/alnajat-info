<?php

namespace App\Console\Commands;

use App\Models\BlockedIp;
use App\Support\Firewall;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** منفذ النجاة من الخادم إن حُظر عنوانك ولم تستطع دخول اللوحة. ولمنع تكراره: FIREWALL_ALLOW في .env */
#[Signature('firewall:unblock {ip? : العنوان} {--all : رفع الحظر عن كل العناوين} {--list : عرض العناوين المحظورة الآن}')]
#[Description('رفع حظر عنوان IP (أو الكل)، أو عرض المحظورين')]
class FirewallUnblock extends Command
{
    public function handle(): int
    {
        if ($this->option('list')) {
            $rows = BlockedIp::active()->orderByDesc('updated_at')->limit(200)->get()
                ->map(fn (BlockedIp $b) => [$b->ip, $b->reasonLabel(), $b->strikes, $b->blocked_until?->format('Y-m-d H:i') ?? 'دائم', $b->trigger]);
            $this->table(['IP', 'السبب', 'المرات', 'حتى', 'الرابط/الملاحظة'], $rows);

            return self::SUCCESS;
        }

        if ($this->option('all')) {
            $count = BlockedIp::active()->update(['blocked_until' => now()->subSecond()]);
            Firewall::forget();
            $this->info("رُفع الحظر عن {$count} عنوان.");

            return self::SUCCESS;
        }

        $ip = (string) $this->argument('ip');
        if ($ip === '') {
            $this->error('اكتب العنوان، أو استخدم --all أو --list');

            return self::FAILURE;
        }

        if (! Firewall::unblock($ip)) {
            $this->warn("العنوان {$ip} غير محظور الآن.");

            return self::SUCCESS;
        }

        $this->info("رُفع الحظر عن {$ip}.");

        return self::SUCCESS;
    }
}
