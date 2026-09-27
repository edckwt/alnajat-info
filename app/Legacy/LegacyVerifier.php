<?php

namespace App\Legacy;

use PDO;

/**
 * يقارن القاعدة الجديدة بالقديمة بعد الاستيراد، صفاً بصف.
 *
 * لا يثق بتقرير الاستيراد: يعيد قراءة الجهتين ويطابق المعرّفات
 * والنصوص (ببصمة md5) والعلاقات والإعدادات. أي فرق يُطبع بمعرّف الصف.
 */
final class LegacyVerifier
{
    private const CHUNK = 1000;

    /** @var list<array{check: string, ok: bool, detail: string}> */
    private array $results = [];

    public function __construct(
        private readonly PDO $source,
        private readonly PDO $target,
        private readonly LegacyTransform $t,
        private readonly int $homeBoxes = 15,
    ) {
        foreach ([$this->source, $this->target] as $pdo) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
    }

    /** @return list<array{check: string, ok: bool, detail: string}> */
    public function run(): array
    {
        $this->results = [];

        $this->verifyUsers();
        $this->verifySimple('categories', 'category', 'title', 'name');
        $this->verifySimple('newspapers', 'newspaper', 'name', 'name');
        $this->verifySimple('banners', 'banners', 'title', 'title');
        $this->verifySimple('uploads', 'upload', 'title', 'title');
        $this->verifyNews();
        $this->verifyCategoryNews();
        $this->verifyPublications();
        $this->verifySettings();

        return $this->results;
    }

    public function passed(): bool
    {
        foreach ($this->results as $r) {
            if (! $r['ok']) {
                return false;
            }
        }

        return true;
    }

    // ------------------------------------------------------------------

    private function verifyUsers(): void
    {
        $old = $this->keyed($this->source, 'SELECT id, username, email, password, active FROM users');
        $new = $this->keyed($this->target, 'SELECT id, username, email, legacy_password, password, is_active FROM users');

        $problems = $this->compareIds('users', $old, $new);

        foreach ($old as $id => $o) {
            if (! isset($new[$id])) {
                continue;
            }
            $n = $new[$id];

            if ($this->t->string($o['username']) !== $n['username']) {
                $problems[] = "#{$id} اسم المستخدم مختلف";
            }
            if ($this->t->string($o['email']) !== $n['email']) {
                $problems[] = "#{$id} البريد مختلف";
            }
            if ($this->t->bool($o['active']) !== (int) $n['is_active']) {
                $problems[] = "#{$id} حالة التفعيل مختلفة";
            }

            // إما أن الهاش القديم محفوظ كما هو، أو أن العضو دخل وترقّى إلى bcrypt.
            $legacyKept = $n['legacy_password'] !== null
                && strtolower((string) $o['password']) === $n['legacy_password'];
            $upgraded = $n['legacy_password'] === null && $n['password'] !== null;

            if (LegacyPassword::looksLegacy($o['password']) && ! $legacyKept && ! $upgraded) {
                $problems[] = "#{$id} كلمة المرور لم تُنقل";
            }
        }

        $this->record('المستخدمون: المعرّفات والدخول وكلمات المرور', $problems, count($old));
    }

    private function verifySimple(string $table, string $legacyTable, string $legacyTitle, string $title): void
    {
        $old = $this->keyed($this->source, "SELECT id, {$legacyTitle} AS t FROM {$legacyTable}");
        $new = $this->keyed($this->target, "SELECT id, {$title} AS t FROM {$table}");

        $problems = $this->compareIds($table, $old, $new);

        foreach ($old as $id => $o) {
            if (isset($new[$id]) && ($this->t->string($o['t']) ?? '') !== (string) $new[$id]['t']) {
                $problems[] = "#{$id} العنوان مختلف";
            }
        }

        $this->record("{$table}: المعرّفات والعناوين", $problems, count($old));
    }

    private function verifyNews(): void
    {
        $problems = [];
        $maxOld = (int) $this->source->query('SELECT COALESCE(MAX(id), 0) FROM news')->fetchColumn();
        $maxNew = (int) $this->target->query('SELECT COALESCE(MAX(id), 0) FROM news')->fetchColumn();
        $max = max($maxOld, $maxNew);
        $total = 0;

        for ($from = 0; $from <= $max; $from += self::CHUNK) {
            $to = $from + self::CHUNK;

            $old = $this->keyed($this->source,
                "SELECT id, title, text, published_date, active, newspaper_id FROM news WHERE id > {$from} AND id <= {$to}");
            $new = $this->keyed($this->target,
                "SELECT id, title, body, published_date, is_active, newspaper_id FROM news WHERE id > {$from} AND id <= {$to}");

            $total += count($old);
            array_push($problems, ...$this->compareIds('news', $old, $new));

            foreach ($old as $id => $o) {
                if (! isset($new[$id])) {
                    continue;
                }
                $n = $new[$id];

                $oldPrint = $this->t->fingerprint($this->t->string($o['title']) ?? '', $o['text']);
                $newPrint = $this->t->fingerprint($n['title'], $n['body']);

                if ($oldPrint !== $newPrint) {
                    $problems[] = "#{$id} العنوان أو النص مختلف";
                }
                if ($this->t->date($o['published_date']) !== $n['published_date']) {
                    $problems[] = "#{$id} تاريخ النشر مختلف";
                }
                if ($this->t->bool($o['active']) !== (int) $n['is_active']) {
                    $problems[] = "#{$id} حالة النشر مختلفة";
                }
                if ((int) $o['newspaper_id'] !== (int) ($n['newspaper_id'] ?? 0)
                    && $this->exists($this->target, 'newspapers', (int) $o['newspaper_id'])) {
                    $problems[] = "#{$id} الصحيفة مختلفة";
                }
            }
        }

        $this->record('الأخبار: المعرّفات والعناوين والنصوص (بصمة) والتواريخ', $problems, $total);
    }

    private function verifyCategoryNews(): void
    {
        // المتوقع = روابط news_meta الصالحة (خبر موجود + قسم موجود)، بلا تكرار.
        $expected = [];
        $validNews = $this->idSet($this->target, 'news');
        $validCats = $this->idSet($this->target, 'categories');

        foreach ($this->source->query("SELECT news_id, meta_value FROM news_meta WHERE meta_key = 'category_id'") as $r) {
            $n = (int) $r['news_id'];
            $c = (int) $r['meta_value'];
            if (isset($validNews[$n], $validCats[$c])) {
                $expected["{$n}:{$c}"] = true;
            }
        }

        $actual = [];
        foreach ($this->target->query('SELECT news_id, category_id FROM category_news') as $r) {
            $actual[$r['news_id'].':'.$r['category_id']] = true;
        }

        $problems = [];
        foreach (array_diff_key($expected, $actual) as $pair => $_) {
            $problems[] = "الرابط {$pair} (خبر:قسم) ناقص";
        }
        foreach (array_diff_key($actual, $expected) as $pair => $_) {
            $problems[] = "الرابط {$pair} (خبر:قسم) زائد";
        }

        $this->record('تصنيف الأخبار في الأقسام', $problems, count($expected));
    }

    private function verifyPublications(): void
    {
        $old = $this->keyed($this->source, 'SELECT id, title, text, publication_date, active FROM publications');
        $new = $this->keyed($this->target, 'SELECT id, title, body, publication_date, is_active, pdf_version FROM publications');

        $problems = $this->compareIds('publications', $old, $new);

        foreach ($old as $id => $o) {
            if (! isset($new[$id])) {
                continue;
            }
            $n = $new[$id];

            if ($this->t->fingerprint($o['text']) !== $this->t->fingerprint($n['body'])) {
                $problems[] = "#{$id} نص النشرة مختلف";
            }
            if ((int) $n['pdf_version'] !== $this->t->pdfVersion((int) $id)) {
                $problems[] = "#{$id} نسخة قالب الـ PDF غير صحيحة";
            }
            if ($this->t->bool($o['active']) !== (int) $n['is_active']) {
                $problems[] = "#{$id} حالة النشر مختلفة";
            }
        }

        $this->record('النشرات: المعرّفات والنصوص ونسخة القالب', $problems, count($old));
    }

    private function verifySettings(): void
    {
        $old = [];
        foreach ($this->source->query('SELECT meta_key, meta_value FROM setting ORDER BY id') as $r) {
            $old[$r['meta_key']] ??= $r['meta_value'];
        }

        $new = [];
        foreach ($this->target->query('SELECT '.$this->q('key').', '.$this->q('value').' FROM settings') as $r) {
            $new[$r['key']] = $r['value'];
        }

        $boxes = [];
        foreach ($this->target->query('SELECT * FROM home_boxes') as $r) {
            $boxes[$r['context'].':'.$r['position']] = $r;
        }

        $problems = [];
        $boxKey = '/^(pdf_)?box_(category|limit|type|banner|code)_(\d+)$/';

        foreach ($old as $key => $value) {
            if (preg_match($boxKey, $key, $m) === 1) {
                $box = $boxes[($m[1] === 'pdf_' ? 'pdf' : 'home').':'.$m[3]] ?? null;

                if ($box === null) {
                    $problems[] = "الصندوق {$key} غير موجود";

                    continue;
                }

                $column = ['category' => 'category_id', 'limit' => 'items_limit', 'type' => 'type', 'banner' => 'banner_id', 'code' => 'code'][$m[2]];
                $expected = $m[2] === 'code' ? $this->t->string($value) : (int) $value;
                $actual = $m[2] === 'code' ? $box[$column] : (int) ($box[$column] ?? 0);

                // قسم أو بانر محذوف يُنقل فارغاً عن قصد.
                $dangling = in_array($m[2], ['category', 'banner'], true) && $actual === 0 && $expected !== 0;

                if ($expected !== $actual && ! $dangling) {
                    $problems[] = "الصندوق {$key}: القيمة مختلفة";
                }

                continue;
            }

            if (! array_key_exists($key, $new)) {
                $problems[] = "الإعداد {$key} غير موجود";
            } elseif ((string) $new[$key] !== (string) $this->t->settingValue($key, $value)) {
                $problems[] = "الإعداد {$key}: القيمة مختلفة";
            }
        }

        if (count($boxes) !== $this->homeBoxes * 2) {
            $problems[] = 'عدد صناديق الرئيسية والـ PDF '.count($boxes).' والمتوقع '.($this->homeBoxes * 2);
        }

        $this->record('الإعدادات وصناديق الصفحة الرئيسية والـ PDF', $problems, count($old));
    }

    // ------------------------------------------------------------------

    /** @return array<int, array<string,mixed>> */
    private function keyed(PDO $pdo, string $sql): array
    {
        $rows = [];
        foreach ($pdo->query($sql) as $r) {
            $rows[(int) $r['id']] = $r;
        }

        return $rows;
    }

    /** @return array<int,true> */
    private function idSet(PDO $pdo, string $table): array
    {
        $ids = [];
        foreach ($pdo->query("SELECT id FROM {$table}") as $r) {
            $ids[(int) $r['id']] = true;
        }

        return $ids;
    }

    private function exists(PDO $pdo, string $table, int $id): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);

        return $stmt->fetchColumn() !== false;
    }

    /** @return list<string> */
    private function compareIds(string $table, array $old, array $new): array
    {
        $problems = [];
        foreach (array_diff_key($old, $new) as $id => $_) {
            $problems[] = "#{$id} غير موجود في القاعدة الجديدة";
        }
        foreach (array_diff_key($new, $old) as $id => $_) {
            $problems[] = "#{$id} زائد (غير موجود في القديمة)";
        }

        return $problems;
    }

    private function record(string $check, array $problems, int $total): void
    {
        $shown = array_slice($problems, 0, 20);
        $more = count($problems) - count($shown);

        $this->results[] = [
            'check' => $check,
            'ok' => $problems === [],
            'detail' => $problems === []
                ? "{$total} مطابق"
                : implode("\n", $shown).($more > 0 ? "\n… و{$more} فرقاً آخر" : ''),
        ];
    }

    private function q(string $identifier): string
    {
        return $this->target->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'
            ? "`{$identifier}`"
            : "\"{$identifier}\"";
    }
}
