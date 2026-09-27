<?php

namespace App\Legacy;

use Closure;
use PDO;
use RuntimeException;
use Throwable;

/**
 * ينقل بيانات القاعدة القديمة إلى القاعدة الجديدة.
 *
 * - يقرأ من المصدر فقط، ولا يكتب فيه أبداً.
 * - يحافظ على كل المعرّفات (id) كما هي: الروابط العامة ونسخة قالب الـ PDF
 *   تعتمد عليها.
 * - قابل للتكرار: مع fresh=true يفرّغ الجداول الجديدة ثم ينقل كل شيء من جديد.
 * - كل جدول داخل transaction مستقلة.
 *
 * لا يعتمد على Laravel: يأخذ اتصالي PDO جاهزين، فيعمل من أمر Artisan
 * ومن الاختبارات ومن سكربت مستقل.
 */
final class LegacyImporter
{
    private const CHUNK = 500;

    /** ترتيب الحذف عند fresh (الأبناء قبل الآباء). */
    private const TARGET_TABLES = [
        'home_boxes', 'category_news', 'news', 'publications', 'uploads',
        'banners', 'newspapers', 'categories', 'settings', 'users',
    ];

    /** @var array<int,true> */
    private array $userIds = [];

    /** @var array<int,true> */
    private array $categoryIds = [];

    /** @var array<int,true> */
    private array $newspaperIds = [];

    /** @var array<int,true> */
    private array $bannerIds = [];

    /** @var array<int,true> */
    private array $newsIds = [];

    private ImportReport $report;

    public function __construct(
        private readonly PDO $source,
        private readonly PDO $target,
        private readonly LegacyTransform $t,
        private readonly int $homeBoxes = 15,
        private readonly ?Closure $log = null,
    ) {
        foreach ([$this->source, $this->target] as $pdo) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
    }

    public function run(bool $fresh = true): ImportReport
    {
        $this->report = new ImportReport;

        if ($fresh) {
            $this->truncateTarget();
        } elseif ($this->targetHasRows()) {
            throw new RuntimeException('القاعدة الجديدة فيها بيانات. شغّل الأمر مع --fresh لتفريغها أولاً.');
        }

        $this->importUsers();
        $this->importCategories();
        $this->importNewspapers();
        $this->importBanners();
        $this->importNews();
        $this->importCategoryNews();
        $this->importPublications();
        $this->importSettings();
        $this->importUploads();

        return $this->report;
    }

    // ------------------------------------------------------------------
    // الجداول
    // ------------------------------------------------------------------

    private function importUsers(): void
    {
        $this->copy('users', 'SELECT * FROM users ORDER BY id', function (array $r) {
            $hash = $this->t->string($r['password'] ?? null);

            if ($hash !== null && ! LegacyPassword::looksLegacy($hash)) {
                $this->report->note('users', (int) $r['id'], 'هاش كلمة المرور ليس MD5؛ سيحتاج العضو إلى إعادة تعيين كلمة المرور');
                $hash = null;
            }

            $this->userIds[(int) $r['id']] = true;

            return [
                'id' => (int) $r['id'],
                'name' => $this->t->string($r['name']) ?? $this->t->string($r['username']) ?? 'user-'.$r['id'],
                'username' => $this->t->string($r['username']),
                'email' => $this->t->string($r['email']),
                'password' => null,
                'legacy_password' => $hash !== null ? strtolower($hash) : null,
                'role' => $this->t->role($r['user_group'] ?? 0),
                'is_active' => $this->t->bool($r['active']),
                'created_at' => $this->t->timestamp($r['date']),
                'updated_at' => $this->t->timestamp($r['date']),
            ];
        });
    }

    private function importCategories(): void
    {
        $this->copy('categories', 'SELECT * FROM category ORDER BY id', function (array $r) {
            $this->categoryIds[(int) $r['id']] = true;

            return [
                'id' => (int) $r['id'],
                'name' => $this->t->string($r['title']) ?? '',
                'description' => $this->t->string($r['description']),
                'is_active' => $this->t->bool($r['active']),
                'created_by' => $this->userRef($r['user_id']),
                'updated_by' => $this->userRef($r['update_user_id']),
                'created_at' => $this->t->timestamp($r['date']),
                'updated_at' => $this->t->timestamp($r['update_date']) ?? $this->t->timestamp($r['date']),
            ];
        }, 'category');
    }

    private function importNewspapers(): void
    {
        $this->copy('newspapers', 'SELECT * FROM newspaper ORDER BY id', function (array $r) {
            $this->newspaperIds[(int) $r['id']] = true;

            return [
                'id' => (int) $r['id'],
                'name' => $this->t->string($r['name']) ?? '',
                'logo' => $this->t->localPath($r['logo']),
                'url' => $this->t->string($r['url']),
                'country' => $this->t->string($r['country']),
                'description' => $this->t->string($r['description']),
                'body' => $this->t->string($r['text']),
                'type' => (int) $r['type'],
                'is_active' => $this->t->bool($r['active']),
                'created_by' => $this->userRef($r['user_id']),
                'updated_by' => $this->userRef($r['update_user_id']),
                'created_at' => $this->t->timestamp($r['date']),
                'updated_at' => $this->t->timestamp($r['update_date']) ?? $this->t->timestamp($r['date']),
            ];
        }, 'newspaper');
    }

    private function importBanners(): void
    {
        $this->copy('banners', 'SELECT * FROM banners ORDER BY id', function (array $r) {
            $this->bannerIds[(int) $r['id']] = true;

            return [
                'id' => (int) $r['id'],
                'title' => $this->t->string($r['title']) ?? '',
                'url' => $this->t->string($r['url']),
                'image' => $this->t->localPath($r['image']),
                'description' => $this->t->string($r['description']),
                'body' => $this->t->string($r['text']),
                'is_active' => $this->t->bool($r['active']),
                'clicks' => $this->t->int($r['visit']),
                'created_by' => $this->userRef($r['user_id']),
                'updated_by' => $this->userRef($r['update_user_id']),
                'created_at' => $this->t->timestamp($r['date']),
                'updated_at' => $this->t->timestamp($r['update_date']) ?? $this->t->timestamp($r['date']),
            ];
        });
    }

    private function importNews(): void
    {
        $this->copy('news', 'SELECT * FROM news ORDER BY id', function (array $r) {
            $id = (int) $r['id'];
            $this->newsIds[$id] = true;

            $newspaperId = (int) $r['newspaper_id'];
            if ($newspaperId !== 0 && ! isset($this->newspaperIds[$newspaperId])) {
                $this->report->note('news', $id, "الصحيفة {$newspaperId} غير موجودة؛ تُرك الحقل فارغاً");
            }

            $publishedDate = $this->t->date($r['published_date']);
            if ($publishedDate === null && trim((string) $r['published_date']) !== '') {
                $this->report->note('news', $id, 'تاريخ نشر غير صالح: '.$r['published_date']);
            }

            $legacyUser = (int) $r['user_id'];

            return [
                'id' => $id,
                'title' => $this->t->string($r['title']) ?? '',
                'source_url' => $this->t->string($r['url']),
                'image' => $this->t->localPath($r['image']),
                'description' => $this->t->string($r['description']),
                // نص الخبر يُنقل كما هو بلا أي تعديل (يطابقه legacy:verify بالبصمة).
                'body' => $r['text'],
                'newspaper_id' => isset($this->newspaperIds[$newspaperId]) ? $newspaperId : null,
                'newspaper_number' => $this->t->int($r['newspaper_number']),
                'published_date' => $publishedDate,
                'is_active' => $this->t->bool($r['active']),
                'hide_in_pdf' => $this->t->bool($r['hide_in_pdf']),
                'hide_title' => $this->t->bool($r['hide_title']),
                'hide_description' => $this->t->bool($r['hide_description']),
                'hide_more' => $this->t->bool($r['hide_more']),
                'views' => $this->t->int($r['visit']),
                'pdf_views' => $this->t->int($r['visit_pdf']),
                'tweet_url' => $this->t->string($r['tweet_url']),
                'sound_url' => $this->t->string($r['sound_url']),
                'video_url' => $this->t->string($r['video_url']),
                'original_image' => $this->t->bool($r['original_image']),
                'type' => (int) $r['type'],
                'sort_order' => (int) $r['orders'],
                'created_by' => $this->userRef($legacyUser),
                'updated_by' => $this->userRef($r['update_user_id']),
                'legacy_user_id' => $legacyUser > 0 ? $legacyUser : null,
                'created_at' => $this->t->timestamp($r['date']),
                'updated_at' => $this->t->timestamp($r['update_date']) ?? $this->t->timestamp($r['date']),
            ];
        });
    }

    private function importCategoryNews(): void
    {
        $seen = [];

        $this->copy(
            'category_news',
            "SELECT id, meta_value, news_id FROM news_meta WHERE meta_key = 'category_id' ORDER BY id",
            function (array $r) use (&$seen) {
                $newsId = (int) $r['news_id'];
                $categoryId = (int) $r['meta_value'];

                if (! isset($this->newsIds[$newsId])) {
                    $this->report->skip('category_news', (int) $r['id'], "الخبر {$newsId} غير موجود");

                    return null;
                }

                if (! isset($this->categoryIds[$categoryId])) {
                    $this->report->skip('category_news', (int) $r['id'], "القسم {$categoryId} غير موجود (الخبر {$newsId})");

                    return null;
                }

                if (isset($seen[$newsId][$categoryId])) {
                    $this->report->skip('category_news', (int) $r['id'], "تكرار الخبر {$newsId} في القسم {$categoryId}");

                    return null;
                }

                $seen[$newsId][$categoryId] = true;

                return ['news_id' => $newsId, 'category_id' => $categoryId];
            },
            'news_meta',
        );

        $other = (int) $this->source
            ->query("SELECT COUNT(*) FROM news_meta WHERE meta_key <> 'category_id' OR meta_key IS NULL")
            ->fetchColumn();

        if ($other > 0) {
            $this->report->note('category_news', 0, "{$other} صفاً في news_meta بمفتاح غير category_id لم تُنقل");
        }
    }

    private function importPublications(): void
    {
        $dates = [];

        $this->copy('publications', 'SELECT * FROM publications ORDER BY id', function (array $r) use (&$dates) {
            $id = (int) $r['id'];
            $date = $this->t->date($r['publication_date']);

            if ($date !== null && isset($dates[$date])) {
                $this->report->note('publications', $id, "تاريخ مكرر {$date} مع النشرة {$dates[$date]}؛ تُرك التاريخ فارغاً");
                $date = null;
            } elseif ($date !== null) {
                $dates[$date] = $id;
            }

            $legacyUser = (int) $r['user_id'];

            return [
                'id' => $id,
                'title' => $this->t->string($r['title']) ?? ($date ?? ''),
                'description' => $this->t->string($r['description']),
                'url' => $this->t->string($r['url']),
                'image' => $this->t->localPath($r['image']),
                'body' => $r['text'],
                'publication_date' => $date,
                'cover' => (int) $r['cover'],
                'other_file' => $this->t->localPath($r['other_file'] ?? null),
                'pdf_version' => $this->t->pdfVersion($id),
                'is_active' => $this->t->bool($r['active']),
                'created_by' => $this->userRef($legacyUser),
                'updated_by' => $this->userRef($r['update_user_id']),
                'legacy_user_id' => $legacyUser > 0 ? $legacyUser : null,
                'created_at' => $this->t->timestamp($r['date']),
                'updated_at' => $this->t->timestamp($r['update_date']) ?? $this->t->timestamp($r['date']),
            ];
        });
    }

    /**
     * جدول setting القديم (مفتاح/قيمة) يُقسم إلى:
     *  - home_boxes: مفاتيح box_*_N و pdf_box_*_N (N من 1 إلى 15)
     *  - settings: كل ما عداها كما هو.
     */
    private function importSettings(): void
    {
        $all = [];
        foreach ($this->source->query('SELECT meta_key, meta_value FROM setting ORDER BY id') as $r) {
            $key = (string) $r['meta_key'];
            // عند تكرار المفتاح، دالة setting() القديمة كانت تأخذ أول صف (LIMIT 1).
            if (! array_key_exists($key, $all)) {
                $all[$key] = $r['meta_value'];
            }
        }

        $boxKey = '/^(pdf_)?box_(category|limit|type|banner|code)_(\d+)$/';
        $plain = [];

        foreach ($all as $key => $value) {
            if (preg_match($boxKey, $key) !== 1) {
                $plain[] = ['key' => $key, 'value' => $this->t->settingValue($key, $value)];
            }
        }

        $this->insertRows('settings', $plain);
        $this->report->count('settings', count($all), count($plain));

        $boxes = [];
        foreach (['home' => 'box_', 'pdf' => 'pdf_box_'] as $context => $prefix) {
            for ($i = 1; $i <= $this->homeBoxes; $i++) {
                $categoryId = (int) ($all[$prefix.'category_'.$i] ?? 0);
                $bannerId = (int) ($all[$prefix.'banner_'.$i] ?? 0);

                if ($categoryId !== 0 && ! isset($this->categoryIds[$categoryId])) {
                    $this->report->note('home_boxes', $i, "{$context}: القسم {$categoryId} غير موجود");
                }

                $boxes[] = [
                    'context' => $context,
                    'position' => $i,
                    'category_id' => isset($this->categoryIds[$categoryId]) ? $categoryId : null,
                    'items_limit' => (int) ($all[$prefix.'limit_'.$i] ?? 0),
                    'type' => (int) ($all[$prefix.'type_'.$i] ?? 0),
                    'banner_id' => isset($this->bannerIds[$bannerId]) ? $bannerId : null,
                    'code' => $this->t->string($all[$prefix.'code_'.$i] ?? null),
                ];
            }
        }

        $this->insertRows('home_boxes', $boxes);
        $this->report->count('home_boxes', count($boxes), count($boxes));
    }

    private function importUploads(): void
    {
        $this->copy('uploads', 'SELECT * FROM upload ORDER BY id', fn (array $r) => [
            'id' => (int) $r['id'],
            'title' => $this->t->string($r['title']) ?? '',
            'path' => $this->t->localPath($r['url']) ?? '',
            'size' => $this->t->int($r['size']),
            'extension' => $this->t->string($r['type']),
            'mime' => $this->t->string($r['mime']),
            'user_id' => $this->userRef($r['user_id']),
            'created_at' => $this->t->timestamp($r['date']),
            'updated_at' => $this->t->timestamp($r['date']),
        ], 'upload');
    }

    // ------------------------------------------------------------------
    // أدوات
    // ------------------------------------------------------------------

    private function userRef(mixed $legacyId): ?int
    {
        $id = (int) $legacyId;

        return isset($this->userIds[$id]) ? $id : null;
    }

    /**
     * يقرأ صفوف المصدر، يحوّل كل صف، ويكتبها في دفعات داخل transaction.
     * الدالة المحوِّلة تعيد null لتخطي الصف (بعد تسجيل السبب في التقرير).
     */
    private function copy(string $table, string $sql, Closure $map, ?string $sourceTable = null): void
    {
        $this->say("← {$table}");

        $read = 0;
        $written = 0;
        $batch = [];

        $own = $this->begin();

        try {
            foreach ($this->source->query($sql) as $row) {
                $read++;
                $mapped = $map($row);

                if ($mapped === null) {
                    continue;
                }

                $batch[] = $mapped;

                if (count($batch) >= self::CHUNK) {
                    $written += $this->insertRows($table, $batch, false);
                    $batch = [];
                }
            }

            $written += $this->insertRows($table, $batch, false);
            $this->commit($own);
        } catch (Throwable $e) {
            $this->rollBack($own);

            throw new RuntimeException("فشل نقل {$table}: ".$e->getMessage(), 0, $e);
        }

        $this->report->count($table, $read, $written, $sourceTable);
        $this->say("  {$read} صفاً قُرئ، {$written} كُتب");
    }

    /** @param list<array<string,mixed>> $rows */
    private function insertRows(string $table, array $rows, bool $ownTransaction = true): int
    {
        if ($rows === []) {
            return 0;
        }

        $columns = array_keys($rows[0]);
        $quoted = implode(', ', array_map(fn ($c) => $this->quote($c), $columns));
        $placeholders = '('.implode(', ', array_fill(0, count($columns), '?')).')';

        $own = $ownTransaction && $this->begin();

        try {
            foreach (array_chunk($rows, self::CHUNK) as $chunk) {
                $sql = sprintf(
                    'INSERT INTO %s (%s) VALUES %s',
                    $this->quote($table),
                    $quoted,
                    implode(', ', array_fill(0, count($chunk), $placeholders)),
                );

                $values = [];
                foreach ($chunk as $row) {
                    foreach ($columns as $column) {
                        $values[] = $row[$column];
                    }
                }

                $this->target->prepare($sql)->execute($values);
            }

            $this->commit($own);
        } catch (Throwable $e) {
            $this->rollBack($own);

            throw $e;
        }

        return count($rows);
    }

    /**
     * يبدأ transaction إن لم تكن هناك واحدة مفتوحة (مثلاً من RefreshDatabase
     * في الاختبارات). يعيد true إن كان هو من بدأها.
     */
    private function begin(): bool
    {
        if ($this->target->inTransaction()) {
            return false;
        }

        return $this->target->beginTransaction();
    }

    private function commit(bool $own): void
    {
        if ($own) {
            $this->target->commit();
        }
    }

    private function rollBack(bool $own): void
    {
        if ($own && $this->target->inTransaction()) {
            $this->target->rollBack();
        }
    }

    private function truncateTarget(): void
    {
        $this->say('تفريغ جداول القاعدة الجديدة');

        $driver = $this->target->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $this->target->exec('PRAGMA foreign_keys = OFF');
        } else {
            $this->target->exec('SET FOREIGN_KEY_CHECKS = 0');
        }

        try {
            foreach (self::TARGET_TABLES as $table) {
                $this->target->exec('DELETE FROM '.$this->quote($table));
            }
        } finally {
            if ($driver === 'sqlite') {
                $this->target->exec('PRAGMA foreign_keys = ON');
            } else {
                $this->target->exec('SET FOREIGN_KEY_CHECKS = 1');
            }
        }
    }

    private function targetHasRows(): bool
    {
        foreach (self::TARGET_TABLES as $table) {
            if ((int) $this->target->query('SELECT COUNT(*) FROM '.$this->quote($table))->fetchColumn() > 0) {
                return true;
            }
        }

        return false;
    }

    private function quote(string $identifier): string
    {
        $driver = $this->target->getAttribute(PDO::ATTR_DRIVER_NAME);

        return $driver === 'mysql'
            ? '`'.str_replace('`', '``', $identifier).'`'
            : '"'.str_replace('"', '""', $identifier).'"';
    }

    private function say(string $line): void
    {
        if ($this->log !== null) {
            ($this->log)($line);
        }
    }
}
