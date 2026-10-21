<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\Setting;
use App\Support\Firewall;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** حظر العناوين: المحظورون، والحظر اليدوي، والمصيدة والاستثناءات والحد (App\Support\Firewall). */
class FirewallController extends Controller
{
    /** مدد الحظر اليدوي بالدقائق (permanent = دائم). */
    public const DURATIONS = [
        '60' => 'ساعة',
        '1440' => 'يوم',
        '10080' => 'أسبوع',
        '43200' => 'شهر',
        'permanent' => 'دائم حتى يُرفع',
    ];

    /** روابط حقيقية في الموقع: نمط مصيدة يطابق أحدها يُرفض حتى لا يُحظر الزوار. */
    private const REAL_PATHS = [
        '/', '/show/15', '/category/1', '/search/%D8%A7', '/archive.html', '/today-news.html', '/publication/600',
        '/pdf-publication/600', '/pdf-show/15', '/index.php?action=show&id=15', '/cp', '/cp/login', '/cp/news',
        '/storage/upload/news_1.jpg', '/upload/news_1.jpg', '/images/v5/first.jpg', '/css/style.css', '/js/custom.js',
        '/themes/modern/site.css', '/admin/css/app.css', '/admin/vendor/flatpickr/flatpickr.min.js', '/favicon.ico', '/robots.txt',
    ];

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['active', 'expired', 'all'], true) ? $request->query('status') : 'active';

        $blocked = BlockedIp::query()
            ->when($status === 'active', fn ($q) => $q->active())
            ->when($status === 'expired', fn ($q) => $q->expired())
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->query('q').'%';
                $q->where(fn ($w) => $w->where('ip', 'like', $term)->orWhere('trigger', 'like', $term));
            })
            ->with('creator:id,name')
            ->orderByDesc('updated_at')
            ->paginate(50)
            ->withQueryString();

        $trap = (string) Setting::get('firewall_trap_paths');

        return view('admin.firewall.index', [
            'blocked' => $blocked,
            'status' => $status,
            'stats' => [
                'active' => BlockedIp::active()->count(),
                'today' => BlockedIp::where('updated_at', '>=', now()->startOfDay())->count(),
                'hits' => (int) BlockedIp::active()->sum('hits'),
            ],
            'myIp' => $request->ip(),
            'myIpAllowed' => $request->ip() ? Firewall::isAllowed($request->ip()) : false,
            'enabled' => Firewall::enabled(),
            'envDisabled' => ! config('alnajat.firewall.enabled', true),
            'trapText' => trim($trap) !== '' ? $trap : implode("\n", (array) config('alnajat.firewall.trap_paths', [])),
            'trapCustom' => trim($trap) !== '',
            'allowText' => (string) Setting::get('firewall_allowlist'),
            'envAllow' => (array) config('alnajat.firewall.allow', []),
            'limit' => Firewall::notFoundLimit(),
            'minutes' => Firewall::notFoundMinutes(),
            'durations' => self::DURATIONS,
        ]);
    }

    /** حظر يدوي. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ip' => ['required', 'ip', function (string $attribute, mixed $value, Closure $fail) use ($request) {
                if ($value === $request->ip()) {
                    $fail('هذا عنوانك الحالي؛ لا يمكنك حظر نفسك.');
                } elseif (Firewall::isAllowed($value)) {
                    $fail('العنوان ضمن الاستثناءات؛ احذفه منها أولاً.');
                }
            }],
            'duration' => ['nullable', 'in:'.implode(',', array_keys(self::DURATIONS))],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], ['ip' => 'العنوان', 'duration' => 'المدة', 'note' => 'الملاحظة']);

        $duration = $data['duration'] ?? '1440';
        Firewall::block($data['ip'], 'manual', $data['note'] ?? null, $duration === 'permanent' ? null : (int) $duration, $request->user()->id);

        return back()->with('status', "تم حظر {$data['ip']} (".self::DURATIONS[$duration].').');
    }

    public function unblock(BlockedIp $blockedIp): RedirectResponse
    {
        Firewall::unblock($blockedIp->ip);

        return back()->with('status', "رُفع الحظر عن {$blockedIp->ip}.");
    }

    /** حذف السجل كله (يبدأ عدّ التكرار من جديد لو عاد). */
    public function destroy(BlockedIp $blockedIp): RedirectResponse
    {
        $blockedIp->delete();
        Firewall::forget();

        return back()->with('status', "حُذف سجل {$blockedIp->ip}.");
    }

    public function settings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['boolean'],
            'trap_paths' => ['nullable', 'string', 'max:5000', function (string $attribute, mixed $value, Closure $fail) {
                foreach (self::lines($value) as $pattern) {
                    if (mb_strlen($pattern) < 3) {
                        return $fail("النمط «{$pattern}» قصير جداً (3 أحرف على الأقل).");
                    }
                    foreach (self::REAL_PATHS as $real) {
                        if (str_contains(strtolower(rawurldecode($real)), strtolower($pattern))) {
                            return $fail("النمط «{$pattern}» يطابق رابطاً حقيقياً في الموقع ({$real})، فيحظر الزوار.");
                        }
                    }
                }
            }],
            'allowlist' => ['nullable', 'string', 'max:5000', function (string $attribute, mixed $value, Closure $fail) {
                foreach (self::lines($value) as $entry) {
                    if (! self::validIpOrCidr($entry)) {
                        return $fail("«{$entry}» ليس عنوان IP ولا نطاقاً صحيحاً (مثل 203.0.113.7 أو 203.0.113.0/24).");
                    }
                }
            }],
            'not_found_limit' => ['required', 'integer', 'min:5', 'max:1000'],
            'not_found_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ], [], [
            'trap_paths' => 'روابط المصيدة', 'allowlist' => 'الاستثناءات',
            'not_found_limit' => 'عدد الصفحات غير الموجودة', 'not_found_minutes' => 'خلال (دقائق)',
        ]);

        $trap = implode("\n", self::lines($data['trap_paths'] ?? ''));
        $defaults = implode("\n", (array) config('alnajat.firewall.trap_paths', []));

        Setting::put([
            'firewall_enabled' => $request->boolean('enabled') ? '1' : '0',
            'firewall_trap_paths' => $trap === $defaults ? '' : $trap, // كالافتراضية = تتبع تحديثات الكود
            'firewall_allowlist' => implode("\n", self::lines($data['allowlist'] ?? '')),
            'firewall_not_found_limit' => (string) $data['not_found_limit'],
            'firewall_not_found_minutes' => (string) $data['not_found_minutes'],
        ]);

        return redirect()->route('admin.firewall.index')->with('status', 'تم حفظ إعدادات الحظر.');
    }

    /** @return list<string> */
    private static function lines(mixed $text): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $text) ?: []))));
    }

    private static function validIpOrCidr(string $entry): bool
    {
        [$ip, $mask] = array_pad(explode('/', $entry, 2), 2, null);
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        if ($mask === null) {
            return true;
        }
        $max = str_contains($ip, ':') ? 128 : 32;

        return ctype_digit($mask) && (int) $mask >= 0 && (int) $mask <= $max;
    }
}
