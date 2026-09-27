<?php

namespace App\Legacy;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

/**
 * خطة التراجع: يحوّل ما أُضيف أو عُدّل في القاعدة الجديدة منذ تاريخ الانتقال
 * إلى SQL بصيغة جداول الموقع القديم (REPLACE INTO)، ليُشغَّل على القاعدة القديمة
 * إن احتجنا العودة إليه بلا فقد للأخبار الجديدة.
 *
 * لا يكتب في أي قاعدة؛ يُنتج نص SQL فقط. لا يعتمد على Laravel.
 */
final class LegacyExporter
{
    /** @var list<string> */
    public array $warnings = [];

    /** @var array<string,int> */
    public array $counts = [];

    public function __construct(
        private readonly PDO $target,
        private readonly string $since,
        private readonly string $baseUrl,
        private readonly string $timezone = 'Asia/Kuwait',
        private readonly ?PDO $legacy = null,
    ) {
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /** @return iterable<string> أسطر SQL */
    public function statements(): iterable
    {
        yield '-- النجاة: تصدير التعديلات منذ '.$this->since.' إلى صيغة القاعدة القديمة';
        yield 'SET NAMES utf8mb4;';
        yield 'START TRANSACTION;';

        yield from $this->table('category', 'categories', fn (array $r) => [
            'id' => $r['id'], 'title' => $r['name'], 'description' => $r['description'],
            'active' => (int) $r['is_active'],
        ] + $this->audit($r));

        yield from $this->table('newspaper', 'newspapers', fn (array $r) => [
            'id' => $r['id'], 'name' => $r['name'], 'logo' => $this->absolute($r['logo']),
            'url' => $r['url'], 'country' => $r['country'], 'description' => $r['description'],
            'text' => $r['body'], 'active' => (int) $r['is_active'], 'type' => (int) $r['type'],
        ] + $this->audit($r));

        yield from $this->table('banners', 'banners', fn (array $r) => [
            'id' => $r['id'], 'title' => $r['title'], 'url' => $r['url'], 'image' => $this->absolute($r['image']),
            'description' => $r['description'], 'text' => $r['body'], 'active' => (int) $r['is_active'],
            'visit' => (int) $r['clicks'],
        ] + $this->audit($r));

        $newsIds = [];
        yield from $this->table('news', 'news', function (array $r) use (&$newsIds) {
            $newsIds[] = (int) $r['id'];

            return [
                'id' => $r['id'], 'title' => $r['title'], 'url' => $r['source_url'],
                'image' => $this->absolute($r['image']), 'description' => $r['description'], 'text' => $r['body'],
                'newspaper_id' => (int) $r['newspaper_id'], 'newspaper_number' => (int) $r['newspaper_number'],
                'published_date' => $r['published_date'], 'active' => (int) $r['is_active'],
                'hide_in_pdf' => (int) $r['hide_in_pdf'], 'visit' => (int) $r['views'], 'visit_pdf' => (int) $r['pdf_views'],
                'hide_title' => (int) $r['hide_title'], 'hide_description' => (int) $r['hide_description'],
                'hide_more' => (int) $r['hide_more'], 'tweet_url' => $r['tweet_url'], 'sound_url' => $r['sound_url'],
                'video_url' => $r['video_url'], 'original_image' => (int) $r['original_image'],
                'type' => (int) $r['type'], 'orders' => (int) $r['sort_order'],
                'user_id' => (int) ($r['legacy_user_id'] ?? $r['created_by'] ?? 0),
            ] + $this->audit($r, withCreator: false);
        });

        // أقسام الأخبار المصدّرة: تُستبدل بالكامل في news_meta.
        foreach (array_chunk($newsIds, 500) as $chunk) {
            $ids = implode(',', $chunk);
            yield "DELETE FROM `news_meta` WHERE `meta_key` = 'category_id' AND `news_id` IN ({$ids});";

            $rows = $this->target->query("SELECT news_id, category_id FROM category_news WHERE news_id IN ({$ids}) ORDER BY news_id, category_id")->fetchAll();
            foreach (array_chunk($rows, 200) as $part) {
                yield 'INSERT INTO `news_meta` (`meta_key`, `meta_value`, `news_id`) VALUES '
                    .implode(', ', array_map(fn ($r) => "('category_id', ".$this->quote((string) $r['category_id']).', '.(int) $r['news_id'].')', $part)).';';
                $this->counts['news_meta'] = ($this->counts['news_meta'] ?? 0) + count($part);
            }
        }

        yield from $this->table('publications', 'publications', fn (array $r) => [
            'id' => $r['id'], 'title' => $r['title'], 'description' => $r['description'], 'url' => $r['url'],
            'image' => $this->absolute($r['image']), 'text' => $r['body'], 'active' => (int) $r['is_active'],
            'publication_date' => $r['publication_date'], 'cover' => $r['cover'] === null ? null : (int) $r['cover'],
            'other_file' => $this->absolute($r['other_file']),
            'user_id' => (int) ($r['legacy_user_id'] ?? $r['created_by'] ?? 0),
        ] + $this->audit($r, withCreator: false));

        $newUsers = (int) $this->target->query('SELECT COUNT(*) FROM users WHERE created_at >= '.$this->quote($this->since))->fetchColumn();
        if ($newUsers > 0) {
            $this->warnings[] = "{$newUsers} عضواً أُضيفوا في النظام الجديد لا يُصدَّرون (كلمات مرورهم bcrypt لا يفهمها القديم)؛ أضفهم يدوياً إن لزم.";
        }

        if ($this->legacy) {
            yield from $this->deletions();
        } else {
            $this->warnings[] = 'لم تُفحص المحذوفات (بدون --deletes): ما حُذف بعد الانتقال يبقى في القاعدة القديمة.';
        }

        yield 'COMMIT;';
    }

    /**
     * @param  callable(array):array  $map
     * @return iterable<string>
     */
    private function table(string $legacyTable, string $table, callable $map): iterable
    {
        $stmt = $this->target->prepare("SELECT * FROM {$table} WHERE created_at >= ? OR updated_at >= ? ORDER BY id");
        $stmt->execute([$this->since, $this->since]);

        $count = 0;
        while ($row = $stmt->fetch()) {
            $data = $map($row);
            $columns = implode(', ', array_map(fn ($c) => "`{$c}`", array_keys($data)));
            $values = implode(', ', array_map(fn ($v) => $this->quote($v), $data));

            yield "REPLACE INTO `{$legacyTable}` ({$columns}) VALUES ({$values});";
            $count++;
        }

        $this->counts[$legacyTable] = $count;
    }

    /** ما يوجد في القديم وحُذف من الجديد. */
    private function deletions(): iterable
    {
        foreach (['category' => 'categories', 'newspaper' => 'newspapers', 'banners' => 'banners', 'news' => 'news', 'publications' => 'publications'] as $old => $new) {
            $oldIds = $this->legacy->query("SELECT id FROM `{$old}`")->fetchAll(PDO::FETCH_COLUMN);
            $newIds = array_flip($this->target->query("SELECT id FROM {$new}")->fetchAll(PDO::FETCH_COLUMN));

            $gone = array_values(array_filter($oldIds, fn ($id) => ! isset($newIds[$id])));
            $this->counts["{$old} (حذف)"] = count($gone);

            foreach (array_chunk($gone, 500) as $chunk) {
                $ids = implode(',', array_map('intval', $chunk));
                yield "DELETE FROM `{$old}` WHERE `id` IN ({$ids});";
                if ($old === 'news') {
                    yield "DELETE FROM `news_meta` WHERE `news_id` IN ({$ids});";
                }
            }
        }
    }

    /** أعمدة التدقيق القديمة: التواريخ نص Unix timestamp. */
    private function audit(array $r, bool $withCreator = true): array
    {
        return ($withCreator ? ['user_id' => (int) ($r['created_by'] ?? 0)] : []) + [
            'date' => $this->unix($r['created_at'] ?? null),
            'update_user_id' => (int) ($r['updated_by'] ?? 0),
            'update_date' => $this->unix($r['updated_at'] ?? null),
        ];
    }

    private function unix(?string $datetime): string
    {
        if ($datetime === null || trim($datetime) === '') {
            return '';
        }

        return (string) (new DateTimeImmutable($datetime, new DateTimeZone($this->timezone)))->getTimestamp();
    }

    /** الموقع القديم يخزن روابط كاملة للصور. */
    private function absolute(?string $path): ?string
    {
        if ($path === null || $path === '' || preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        return rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
    }

    private function quote(mixed $value): string
    {
        return $value === null ? 'NULL' : $this->target->quote((string) $value);
    }
}

