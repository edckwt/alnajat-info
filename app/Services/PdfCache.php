<?php

namespace App\Services;

use App\Models\News;
use App\Models\Publication;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * ملفات الـ PDF الجاهزة في storage/app/pdf حتى لا يُعاد توليد النشرة
 * (عملية ثقيلة) مع كل زيارة كما كان في النظام القديم.
 *
 * - نشرة يوم سابق: تبقى حتى يتغير شيء يخصها (خبر بتاريخها، النشرة نفسها).
 * - نشرة اليوم: كذلك، مع حد أقصى alnajat.pdf.today_ttl دقيقة احتياطاً.
 * - تغيير عام (الإعدادات، الصناديق، الأقسام، الإعلانات، الصحف): flush()
 *   يرفع رقم «الجيل» فتنتهي كل الملفات دون حذفها دفعة واحدة.
 *
 * بيانات كل ملف (الجيل ووقت التوليد) في الكاش؛ مسح الكاش = إعادة توليد عند الطلب.
 */
class PdfCache
{
    private const GENERATION = 'pdf.generation';

    private const META = 'pdf.meta:';

    public function __construct(private readonly PdfBuilder $builder) {}

    /** @return string مسار الملف على القرص */
    public function publication(Publication $publication): string
    {
        $isCurrent = $publication->publication_date === null
            || $publication->publication_date->toDateString() >= now()->toDateString();

        return $this->remember(
            self::publicationFile($publication->id),
            $isCurrent ? (int) config('alnajat.pdf.today_ttl', 15) : null,
            fn () => $this->builder->publication($publication),
        );
    }

    public function news(News $news): string
    {
        return $this->remember(
            self::newsFile($news->id),
            null,
            fn () => $this->builder->news($news),
        );
    }

    public function isFresh(string $file, ?int $ttlMinutes = null): bool
    {
        $meta = Cache::get(self::META.$file);

        if (! is_array($meta) || $meta['generation'] !== $this->generation() || ! Storage::disk('pdf')->exists($file)) {
            return false;
        }

        return $ttlMinutes === null || $meta['built_at'] > now()->subMinutes($ttlMinutes)->getTimestamp();
    }

    public function forgetPublication(int $id): void
    {
        $this->forget(self::publicationFile($id));
    }

    public function forgetNews(int $id): void
    {
        $this->forget(self::newsFile($id));
    }

    private function forget(string $file): void
    {
        Cache::forget(self::META.$file);
        Storage::disk('pdf')->delete($file);
    }

    private function generation(): int
    {
        return (int) Cache::get(self::GENERATION, 0);
    }

    /** نشرة تاريخ معيّن (أخبار النشرة = أخبار تاريخها). */
    public function forgetDate(mixed $date): void
    {
        $date = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;

        if ($date === '') {
            return;
        }

        Publication::where('publication_date', $date)->pluck('id')
            ->each(fn (int $id) => $this->forgetPublication($id));
    }

    /** كل الملفات المولدة قبل الآن تصبح منتهية (تُستبدل عند أول طلب). */
    public function flush(): void
    {
        Cache::forever(self::GENERATION, $this->generation() + 1);
    }

    public static function publicationFile(int $id): string
    {
        return "publications/{$id}.pdf";
    }

    public static function newsFile(int $id): string
    {
        return "news/{$id}.pdf";
    }

    /** يولّد الملف مرة واحدة حتى لو طلبه عدة زوار في نفس اللحظة. */
    private function remember(string $file, ?int $ttlMinutes, Closure $build): string
    {
        $disk = Storage::disk('pdf');

        if (! $this->isFresh($file, $ttlMinutes)) {
            Cache::lock('pdf:'.$file, 600)->block(180, function () use ($disk, $file, $ttlMinutes, $build) {
                if ($this->isFresh($file, $ttlMinutes)) {
                    return; // ولّده طلب آخر أثناء الانتظار
                }

                @set_time_limit(0);
                $content = $build();

                // كتابة ذرّية: لا يُقدَّم ملف نصف مكتوب لزائر آخر.
                $tmp = $file.'.'.bin2hex(random_bytes(4)).'.tmp';
                $disk->put($tmp, $content);
                rename($disk->path($tmp), $disk->path($file));

                Cache::forever(self::META.$file, ['generation' => $this->generation(), 'built_at' => now()->getTimestamp()]);
            });
        }

        return $disk->path($file);
    }
}
