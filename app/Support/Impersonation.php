<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * الدخول بحساب عضو بلا كلمة مرور (صلاحية users.impersonate، ويملكها مدير النظام دائماً).
 * العضو الأصلي يُحفظ في الجلسة، ويعود إلى حسابه من الشريط الظاهر في كل الصفحات.
 *
 * القيود: لا دخول بحساب موقوف أو بحسابك، ولا بحساب داخل حساب آخر، ولا بحساب مدير أو عضو
 * له صلاحيات لا تملكها (إلا لمدير النظام)، فلا تُكتسب صلاحية عبر هذه الميزة.
 */
final class Impersonation
{
    public const SESSION_KEY = 'impersonator_id';

    public const PERMISSION = 'users.impersonate';

    public static function active(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    /** العضو الأصلي الذي دخل بحساب غيره. */
    public static function impersonator(): ?User
    {
        $id = session(self::SESSION_KEY);

        return $id ? User::find($id) : null;
    }

    /** سبب المنع بالعربية، أو null إن كان مسموحاً. */
    public static function denial(User $actor, User $target): ?string
    {
        return match (true) {
            self::active() => 'أنت داخل حساب عضو آخر؛ عُد إلى حسابك أولاً.',
            ! $actor->can(self::PERMISSION) => 'لا تملك صلاحية الدخول بحسابات الأعضاء.',
            $actor->is($target) => 'هذا حسابك.',
            ! $target->is_active => 'الحساب موقوف؛ فعّله أولاً.',
            ! $actor->isAdmin() && $target->isAdmin() => 'مدير النظام وحده يدخل بحسابات المديرين.',
            ! $actor->isAdmin() && array_diff($target->permissionKeys(), $actor->permissionKeys()) !== [] => 'لهذا العضو صلاحيات لا تملكها.',
            default => null,
        };
    }

    public static function allowed(User $actor, User $target): bool
    {
        return self::denial($actor, $target) === null;
    }

    public static function start(Request $request, User $target): void
    {
        $actor = $request->user();

        Auth::guard('web')->login($target);          // يجدد معرّف الجلسة
        $request->session()->put(self::SESSION_KEY, $actor->id);

        Log::info('impersonation.start', ['by' => $actor->id, 'by_username' => $actor->username, 'as' => $target->id, 'as_username' => $target->username, 'ip' => $request->ip()]);
    }

    /** العودة إلى الحساب الأصلي؛ null إن لم يعد موجوداً أو فعّالاً (فيُسجَّل الخروج). */
    public static function stop(Request $request): ?User
    {
        $original = User::find($request->session()->pull(self::SESSION_KEY));
        $was = $request->user();

        if (! $original || ! $original->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return null;
        }

        Auth::guard('web')->login($original);
        $request->session()->regenerateToken();

        Log::info('impersonation.stop', ['by' => $original->id, 'as' => $was?->id, 'ip' => $request->ip()]);

        return $original;
    }
}
