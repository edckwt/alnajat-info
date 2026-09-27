<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

#[Signature('alnajat:check-links
    {base : عنوان الموقع الجديد المراد فحصه، مثل https://staging.alnajat.info}
    {--sample=200 : عدد الروابط تقريباً}
    {--source=auto : مصدر المعرّفات: legacy (القاعدة القديمة) أو new أو auto}
    {--pdf : فحص روابط الـ PDF أيضاً (أبطأ)}')]
#[Description('فحص عينة من روابط الموقع القديم على الموقع الجديد: كلها 200 أو 301 إلى صفحة 200')]
class CheckLinks extends Command
{
    public const USER_AGENT = 'AlnajatLinkCheck/1.0';

    public function handle(): int
    {
        $base = rtrim((string) $this->argument('base'), '/');
        $urls = $this->sample(max(20, (int) $this->option('sample')));

        $this->info(count($urls).' رابطاً على '.$base);
        $bar = $this->output->createProgressBar(count($urls));

        $failures = [];
        $report = [['url', 'status', 'final', 'result']];

        foreach ($urls as $path) {
            [$ok, $status, $final] = $this->check($base, $path);
            $report[] = [$path, $status, $final, $ok ? 'ok' : 'FAIL'];
            if (! $ok) {
                $failures[] = [$path, $status, $final];
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $file = storage_path('app/private/link-check-'.now()->format('Ymd-His').'.csv');
        @mkdir(dirname($file), 0775, true);
        $handle = fopen($file, 'w');
        foreach ($report as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        if ($failures !== []) {
            $this->table(['الرابط', 'الحالة', 'النهاية'], $failures);
            $this->error(count($failures).' رابطاً فشل من '.count($urls).'. التقرير: '.$file);

            return self::FAILURE;
        }

        $this->info('كل الروابط سليمة. التقرير: '.$file);

        return self::SUCCESS;
    }

    /** @return array{0: bool, 1: int|string, 2: string} */
    private function check(string $base, string $path): array
    {
        $http = Http::withUserAgent(self::USER_AGENT)->withoutRedirecting()->timeout(300);

        try {
            $response = $http->get($base.'/'.ltrim($path, '/'));

            if ($response->status() === 200) {
                return [true, 200, ''];
            }

            if ($response->status() !== 301) {
                return [false, $response->status(), ''];
            }

            // رابط قديم: تحويل دائم واحد إلى صفحة تعمل
            $location = (string) $response->header('Location');
            $target = str_starts_with($location, 'http') ? $location : $base.'/'.ltrim($location, '/');
            $final = $http->get($target);

            return [$final->status() === 200, 301, $location.' → '.$final->status()];
        } catch (Throwable $e) {
            return [false, 'error', mb_substr($e->getMessage(), 0, 120)];
        }
    }

    /** @return list<string> */
    private function sample(int $size): array
    {
        [$db, $legacy] = $this->source();

        $table = fn (string $old, string $new) => $legacy ? $db->table($old) : $db->table($new);
        $active = $legacy ? 'active' : 'is_active';
        $random = fn (string $old, string $new, int $limit) => $table($old, $new)
            ->where($active, 1)->inRandomOrder()->limit($limit)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $urls = ['/', '?all', 'archive.html', 'index.php?action=publications', 'search/'.rawurlencode('الكويت'), '?action=search&s='.rawurlencode('النجاة')];

        foreach ($random('news', 'news', (int) round($size * 0.6)) as $i => $id) {
            $urls[] = $i % 2 ? "show/{$id}" : "index.php?action=show&id={$id}";
        }

        foreach ($random('category', 'categories', 50) as $id) {
            $urls[] = "category/{$id}";
            $urls[] = "?action=category&id={$id}";
        }

        foreach ($random('publications', 'publications', (int) round($size * 0.2)) as $i => $id) {
            $urls[] = $i % 2 ? "publication/{$id}" : "index.php?action=publication&publication_id={$id}";
        }

        if ($this->option('pdf')) {
            $urls[] = 'today-news.html';
            $urls[] = 'index.php?read=pdf&online=1';
            foreach ($random('publications', 'publications', 5) as $id) {
                $urls[] = "pdf-publication/{$id}";
            }
            foreach ($random('news', 'news', 5) as $id) {
                $urls[] = "pdf-show/{$id}";
            }
        }

        return array_values(array_unique($urls));
    }

    /** @return array{0: \Illuminate\Database\ConnectionInterface, 1: bool} */
    private function source(): array
    {
        $source = $this->option('source');

        if ($source !== 'new') {
            try {
                $legacy = DB::connection('legacy');
                $legacy->getPdo();
                $this->line('المعرّفات من القاعدة القديمة (الروابط الموجودة فعلاً على الإنترنت).');

                return [$legacy, true];
            } catch (Throwable $e) {
                if ($source === 'legacy') {
                    throw $e;
                }
            }
        }

        $this->line('المعرّفات من القاعدة الجديدة.');

        return [DB::connection(), false];
    }
}
