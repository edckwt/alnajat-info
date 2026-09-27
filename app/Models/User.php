<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Support\Permissions;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'avatar', 'phone', 'bio', 'password', 'role', 'permissions', 'is_active'])]
#[Hidden(['password', 'legacy_password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'permissions' => 'array',
        ];
    }

    /** رابط الصورة الشخصية (أو null فتُعرض الحروف الأولى). */
    public function avatarUrl(string $size = 'xsmall'): ?string
    {
        return $this->avatar ? \App\Support\Media::thumb($this->avatar, $size) : null;
    }

    /** أول حرفين من الاسم لصورة بديلة. */
    public function initials(): string
    {
        $words = preg_split('/\s+/u', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];

        return mb_substr($words[0], 0, 1).(isset($words[1]) ? mb_substr($words[1], 0, 1) : '');
    }

    /** اسم الدور للعرض. */
    public function roleName(): string
    {
        return $this->roleRecord()?->name ?? (string) config('alnajat.roles.'.$this->role, $this->role);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN;
    }

    /** الدور (users.role = roles.key). يُقرأ مرة واحدة لكل عضو في الطلب. */
    public function roleRecord(): ?Role
    {
        return once(fn () => $this->role ? Role::firstWhere('key', $this->role) : null);
    }

    /**
     * صلاحيات العضو الفعلية: صلاحيات دوره + صلاحياته الإضافية.
     *
     * @return list<string>
     */
    public function permissionKeys(): array
    {
        if ($this->isAdmin()) {
            return Permissions::all();
        }

        return once(fn () => Permissions::normalize(array_merge(
            $this->roleRecord()?->permissions ?? [],
            $this->permissions ?? [],
        )));
    }

    public function hasPermission(string $key): bool
    {
        return $this->isAdmin() || in_array($key, $this->permissionKeys(), true);
    }

    /** لم يدخل بعد منذ الهجرة، فما زال يحمل هاش MD5 القديم. */
    public function hasLegacyPassword(): bool
    {
        return $this->legacy_password !== null;
    }
}
