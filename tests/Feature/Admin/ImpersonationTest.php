<?php

use App\Models\Role;
use App\Models\User;
use App\Support\Impersonation;

function impUser(string $role, string $username, array $permissions = [], bool $active = true): User
{
    return User::forceCreate([
        'name' => 'عضو '.$username, 'username' => $username, 'email' => "$username@example.com",
        'password' => 'secret-123', 'role' => $role, 'permissions' => $permissions, 'is_active' => $active,
    ]);
}

beforeEach(function () {
    $this->admin = impUser('admin', 'boss');
    $this->editor = impUser('editor', 'sara');
});

it('is a permission that the system admin has', function () {
    expect(\App\Support\Permissions::exists('users.impersonate'))->toBeTrue()
        ->and($this->admin->can('users.impersonate'))->toBeTrue()
        ->and($this->editor->can('users.impersonate'))->toBeFalse();
});

it('lets the admin log in as a member without a password and come back', function () {
    $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk()
        ->assertSee(route('admin.users.impersonate', $this->editor), false)
        ->assertDontSee(route('admin.users.impersonate', $this->admin), false); // ليس لحسابه

    $this->post(route('admin.users.impersonate', $this->editor))->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($this->editor);
    expect(session(Impersonation::SESSION_KEY))->toBe($this->admin->id)
        ->and($this->editor->fresh()->last_login_at)->toBeNull(); // ليس دخولاً حقيقياً

    // الشريط والعودة ظاهران، وصلاحيات المحرر هي المطبّقة
    $this->get(route('admin.dashboard'))->assertOk()->assertSee('data-impersonation-bar', false)->assertSee('العودة إلى حسابي');
    $this->get(route('admin.users.index'))->assertForbidden();

    // لا تغيير لكلمة مرور العضو ولا إنهاء جلساته أثناء ذلك
    $this->from(route('admin.profile.show', ['tab' => 'security']))->put(route('admin.profile.password'), [
        'current_password' => 'secret-123', 'password' => 'new-secret-9', 'password_confirmation' => 'new-secret-9',
    ])->assertSessionHasErrors('current_password');
    expect(\Illuminate\Support\Facades\Hash::check('secret-123', $this->editor->fresh()->password))->toBeTrue();

    $this->delete(route('admin.impersonate.leave'))->assertRedirect(route('admin.users.index'));
    $this->assertAuthenticatedAs($this->admin);
    expect(session()->has(Impersonation::SESSION_KEY))->toBeFalse();

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('data-impersonation-bar', false);
});

it('refuses members without the permission, inactive accounts and nested impersonation', function () {
    $this->actingAs($this->editor)->post(route('admin.users.impersonate', $this->admin))->assertForbidden();

    $stopped = impUser('editor', 'stopped', active: false);
    $this->actingAs($this->admin)->post(route('admin.users.impersonate', $stopped))->assertSessionHasErrors('impersonate');
    $this->assertAuthenticatedAs($this->admin);

    $this->post(route('admin.users.impersonate', $this->editor));
    $this->post(route('admin.users.impersonate', $stopped))->assertForbidden(); // المحرر لا يملك الصلاحية
    expect(Impersonation::denial($this->admin, $stopped))->not->toBeNull();

    $this->delete(route('admin.impersonate.leave'));
    $this->assertAuthenticatedAs($this->admin);
    $this->delete(route('admin.impersonate.leave'))->assertNotFound(); // لا جلسة دخول بحساب آخر
});

it('never lets a non-admin gain permissions through another account', function () {
    Role::forceCreate(['key' => 'support', 'name' => 'دعم', 'permissions' => ['users.view', 'users.impersonate', 'news.view']]);
    $support = impUser('support', 'help');
    $reader = impUser('support', 'reader');
    $writer = impUser('editor', 'writer'); // صلاحيات لا يملكها الدعم

    expect(Impersonation::allowed($support, $reader))->toBeTrue()
        ->and(Impersonation::denial($support, $writer))->toBe('لهذا العضو صلاحيات لا تملكها.')
        ->and(Impersonation::denial($support, $this->admin))->toBe('مدير النظام وحده يدخل بحسابات المديرين.');

    $this->actingAs($support)->post(route('admin.users.impersonate', $writer))->assertSessionHasErrors('impersonate');
    $this->assertAuthenticatedAs($support);

    $this->post(route('admin.users.impersonate', $reader))->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($reader);
});
