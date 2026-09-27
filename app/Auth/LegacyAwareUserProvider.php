<?php

namespace App\Auth;

use App\Legacy\LegacyPassword;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * مزوّد Eloquent يفهم كلمات المرور القديمة.
 *
 * العضو المنقول من النظام القديم يحمل هاش MD5 في legacy_password و password فارغ.
 * عند أول دخول ناجح يُحفظ bcrypt ويُمسح الهاش القديم، فلا يلاحظ العضو شيئاً.
 *
 * النظام القديم كان يطبّق trim() على كلمة المرور قبل مقارنتها، فنحافظ على
 * ذلك: " سر " و "سر" كلمة مرور واحدة قبل الترقية وبعدها.
 */
class LegacyAwareUserProvider extends EloquentUserProvider
{
    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        $plain = $credentials['password'] ?? null;

        if (! is_string($plain) || $plain === '') {
            return false;
        }

        $hashed = $user->getAuthPassword();

        if ($hashed !== null && $hashed !== '') {
            return $this->hasher->check(trim($plain), $hashed)
                || ($plain !== trim($plain) && $this->hasher->check($plain, $hashed));
        }

        $legacy = $user->legacy_password ?? null;

        if (! LegacyPassword::matches($plain, $legacy)) {
            return false;
        }

        $user->forceFill([
            $user->getAuthPasswordName() => $this->hasher->make(trim($plain)),
            'legacy_password' => null,
        ])->save();

        return true;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
    {
        $hashed = $user->getAuthPassword();

        if ($hashed === null || $hashed === '' || (! $this->hasher->needsRehash($hashed) && ! $force)) {
            return;
        }

        $user->forceFill([
            $user->getAuthPasswordName() => $this->hasher->make(trim((string) $credentials['password'])),
        ])->save();
    }
}
