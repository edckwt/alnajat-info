<?php

namespace App\Legacy;

/**
 * ما حدث أثناء الاستيراد: أعداد كل جدول، والصفوف المتخطاة وسببها،
 * والملاحظات على صفوف نُقلت بعد تعديل (مثل مرجع لمستخدم محذوف).
 */
final class ImportReport
{
    /** @var array<string, array{source: ?string, read: int, written: int}> */
    public array $tables = [];

    /** @var list<array{table: string, id: int, reason: string}> */
    public array $skipped = [];

    /** @var list<array{table: string, id: int, reason: string}> */
    public array $notes = [];

    public function count(string $table, int $read, int $written, ?string $source = null): void
    {
        $this->tables[$table] = ['source' => $source ?? $table, 'read' => $read, 'written' => $written];
    }

    public function skip(string $table, int $id, string $reason): void
    {
        $this->skipped[] = compact('table', 'id', 'reason');
    }

    public function note(string $table, int $id, string $reason): void
    {
        $this->notes[] = compact('table', 'id', 'reason');
    }

    public function skippedCount(string $table): int
    {
        return count(array_filter($this->skipped, fn ($s) => $s['table'] === $table));
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'tables' => $this->tables,
            'skipped' => $this->skipped,
            'notes' => $this->notes,
        ];
    }
}
