<?php

use App\Legacy\LegacyExporter;
use App\Models\Category;
use App\Models\News;
use Illuminate\Support\Facades\DB;

it('exports only rows changed since the cutover, in the old table format', function () {
    Category::forceCreate(['id' => 1, 'name' => 'محليات', 'is_active' => true, 'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-01 10:00:00']);

    $old = News::forceCreate(['title' => 'قبل الانتقال', 'type' => 1, 'is_active' => true, 'published_date' => '2026-01-01', 'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-01 10:00:00']);
    $new = News::forceCreate([
        'title' => "بعد الانتقال 'مقتبس'", 'type' => 1, 'is_active' => true, 'published_date' => '2026-10-02',
        'image' => 'upload/news_1.png', 'sort_order' => 4, 'views' => 7,
        'created_at' => '2026-10-02 09:00:00', 'updated_at' => '2026-10-02 09:00:00',
    ]);
    $new->categories()->attach(1);

    $exporter = new LegacyExporter(DB::connection()->getPdo(), '2026-10-01 00:00:00', 'https://www.alnajat.info', 'Asia/Kuwait');
    $sql = implode("\n", iterator_to_array($exporter->statements(), false));

    expect($sql)
        ->toContain('REPLACE INTO `news`')
        ->toContain("'بعد الانتقال ''مقتبس'''")
        ->toContain("'https://www.alnajat.info/upload/news_1.png'")
        ->toContain("'".(new DateTimeImmutable('2026-10-02 09:00:00', new DateTimeZone('Asia/Kuwait')))->getTimestamp()."'")
        ->toContain("('category_id', '1', {$new->id})")
        ->not->toContain('قبل الانتقال')
        ->not->toContain('REPLACE INTO `category`')
        ->and($exporter->counts['news'])->toBe(1)
        ->and($exporter->warnings)->not->toBeEmpty(); // المحذوفات لم تُفحص
});
