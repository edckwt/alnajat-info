<?php

namespace App\Support;

use App\Models\BlockedIp;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;
use Throwable;

/**
 * حظر العناوين التي تتصرف كالبوتات المتلصصة (Laravel فقط، بلا Cloudflare):
 *
 *  1. المصيدة: طلب رابط لا يطلبه زائر حقيقي (/.git، /.env، wp-login.php …) = حظر فوري.
 *  2. الحدّ: صفحات غير موجودة كثيرة خلال دقائق (الافتراضي 30 خلال 5) = حظر.
 *  3. الحظر مؤقت ويطول مع التكرار: يوم، ثم أسبوع، ثم شهر؛ أو يدوي من اللوحة (مؤقت أو دائم).
 *
 * لا يُحظر أبداً: 127.0.0.1 و::1، وFIREWALL_ALLOW في .env، وقائمة الاستثناءات في اللوحة.
 * الفحص مع كل طلب من الكاش (قائمة العناوين النشطة)، بلا استعلام على القاعدة.
 * عنوان الزائر الحقيقي من خلف Cloudflare يأتي من trustProxies في bootstrap/app.php.
 */
final class Firewall
{
    public const CACHE_KEY = 'firewall.blocked';

    /** مفاتيح الإعدادات التي تُعدَّل من اللوحة. */
    public const SETTINGS = ['firewall_enabled', 'firewall_trap_paths', 'firewall_allowlist', 'firewall_not_found_limit', 'firewall_not_found_minutes'];

    private const LOCAL = ['127.0.0.1', '::1'];

    /** ملفات ثابتة: صور مكسورة في نص خبر قديم لا تجعل الزائر «بوتاً». */
    private const STATIC_FILES = '/\.(jpe?g|png|gif|webp|svg|ico|bmp|css|js|map|woff2?|ttf|eot|otf|mp3|m4a|mp4|webm|pdf)$/i';

    /** محركات البحث قد تطلب روابط قديمة كثيرة بعد الانتقال؛ لا تُحتسب في حد الأخطاء (المصيدة تبقى عليها). */
    private const GOOD_BOTS = '/googlebot|bingbot|applebot|yandex|duckduckbot|baiduspider|facebookexternalhit|twitterbot|linkedinbot|slurp|petalbot/i';

    public static function enabled(): bool
    {
        if (! config('alnajat.firewall.enabled', true)) {
            return false;
        }

        $value = self::setting('firewall_enabled');

        return $value === null || $value === '' || (string) $value === '1';
    }

    /** @return list<string> */
    public static function trapPaths(): array
    {
        $custom = self::lines(self::setting('firewall_trap_paths'));

        return $custom !== [] ? $custom : (array) config('alnajat.firewall.trap_paths', []);
    }

    /** @return list<string> عناوين ونطاقات CIDR لا تُحظر أبداً */
    public static function allowlist(): array
    {
        return array_values(array_unique([
            ...self::LOCAL,
            ...(array) config('alnajat.firewall.allow', []),
            ...self::lines(self::setting('firewall_allowlist')),
        ]));
    }

    public static function notFoundLimit(): int
    {
        return max(5, (int) (self::setting('firewall_not_found_limit') ?: config('alnajat.firewall.not_found_limit', 30)));
    }

    public static function notFoundMinutes(): int
    {
        return max(1, (int) (self::setting('firewall_not_found_minutes') ?: config('alnajat.firewall.not_found_minutes', 5)));
    }

    public static function isAllowed(string $ip): bool
    {
        try {
            return IpUtils::checkIp($ip, self::allowlist());
        } catch (Throwable) {
            return false; // نطاق مكتوب خطأً في الاستثناءات لا يكسر الموقع
        }
    }

    public static function isBlocked(string $ip): bool
    {
        $until = self::map()[$ip] ?? null;

        return $until !== null && ($until === 0 || $until > now()->getTimestamp());
    }

    /** الجزء المطابق من المصيدة في الرابط (بعد فك الترميز)، أو null. */
    public static function trapMatch(Request $request): ?string
    {
        $uri = strtolower(rawurldecode(rawurldecode($request->getRequestUri())));

        foreach (self::trapPaths() as $pattern) {
            $pattern = strtolower(trim($pattern));
            if ($pattern !== '' && str_contains($uri, $pattern)) {
                return $pattern;
            }
        }

        return null;
    }

    /**
     * حظر عنوان. $minutes: -1 = حسب مرات التكرار (يوم ثم أسبوع ثم شهر)، null = دائم، وإلا عدد الدقائق.
     * حظر قائم أطول (أو دائم) لا يُقصَّر.
     */
    public static function block(string $ip, string $reason, ?string $trigger = null, ?int $minutes = -1, ?int $by = null): BlockedIp
    {
        $record = BlockedIp::firstWhere('ip', $ip);
        $recent = $record && $record->updated_at?->gt(now()->subDays((int) config('alnajat.firewall.strike_reset_days', 90)));
        $strikes = $recent ? $record->strikes + 1 : 1;

        if ($minutes === -1) {
            $durations = (array) config('alnajat.firewall.durations', [1440, 10080, 43200]);
            $minutes = (int) ($durations[min($strikes, count($durations)) - 1] ?? 1440);
        }

        $until = $minutes === null ? null : now()->addMinutes($minutes);
        if ($record && $record->isActive() && ($record->isPermanent() || ($until && $record->blocked_until->gt($until)))) {
            $until = $record->blocked_until; // لا يُقصَّر حظر قائم
        }

        $record ??= new BlockedIp(['ip' => $ip]);
        $record->fill([
            'reason' => $reason,
            'trigger' => $trigger !== null ? Str::limit($trigger, 990, '') : null,
            'strikes' => $strikes,
            'blocked_until' => $until,
            'created_by' => $by,
        ]);
        $record->updated_at = now(); // آخر حظر (لتصاعد المدة)، حتى لو لم يتغير غيره
        $record->save();

        self::forget();
        Log::warning('firewall.block', ['ip' => $ip, 'reason' => $reason, 'trigger' => $trigger, 'strikes' => $strikes, 'until' => $until?->toDateTimeString() ?? 'forever', 'by' => $by]);

        return $record;
    }

    /** رفع الحظر الآن؛ يبقى السجل (فيطول الحظر إن عاد خلال 90 يوماً). */
    public static function unblock(string $ip): bool
    {
        $done = BlockedIp::where('ip', $ip)->active()->update(['blocked_until' => now()->subSecond()]) > 0;
        self::forget();

        return $done;
    }

    /** يُستدعى مع كل 404 (bootstrap/app.php): صفحات غير موجودة كثيرة خلال دقائق = حظر. */
    public static function recordNotFound(Request $request): void
    {
        $ip = $request->ip();
        if (! $ip || ! self::enabled() || self::isAllowed($ip)
            || preg_match(self::STATIC_FILES, (string) parse_url($request->getRequestUri(), PHP_URL_PATH))
            || preg_match(self::GOOD_BOTS, (string) $request->userAgent())) {
            return;
        }

        try {
            $key = 'firewall:404:'.sha1($ip);
            if (RateLimiter::hit($key, self::notFoundMinutes() * 60) >= self::notFoundLimit()) {
                RateLimiter::clear($key);
                self::block($ip, 'flood', Str::limit(rawurldecode($request->getRequestUri()), 990, ''));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** عدّ الطلبات المرفوضة: يُجمع في الكاش ويُكتب في القاعدة مرة في الدقيقة على الأكثر لكل عنوان. */
    public static function countBlockedHit(string $ip): void
    {
        try {
            $key = 'firewall:hits:'.sha1($ip);
            Cache::add($key, 0, 3600);
            Cache::increment($key);

            if (Cache::add('firewall:flush:'.sha1($ip), 1, 60)) {
                DB::table('blocked_ips')->where('ip', $ip)->increment('hits', max(1, (int) Cache::pull($key, 1)));
            }
        } catch (Throwable) {
            // العدّ للمعلومة فقط
        }
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string,int> العنوان => وقت انتهاء الحظر (0 = دائم) للعناوين النشطة */
    private static function map(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => BlockedIp::query()->active()
                ->get(['ip', 'blocked_until'])
                ->mapWithKeys(fn (BlockedIp $b) => [$b->ip => $b->blocked_until?->getTimestamp() ?? 0])
                ->all());
        } catch (Throwable) {
            return []; // قبل تشغيل migrate
        }
    }

    private static function setting(string $key): mixed
    {
        try {
            return Setting::get($key);
        } catch (Throwable) {
            return null;
        }
    }

    /** @return list<string> */
    private static function lines(mixed $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $text) ?: [])));
    }
}
