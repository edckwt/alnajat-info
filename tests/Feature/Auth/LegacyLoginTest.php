<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// عضو منقول من النظام القديم: هاش MD5 في legacy_password و password فارغ.
function legacyMember(array $attributes = []): User
{
    return User::forceCreate(array_merge([
        'name' => 'علاء',
        'username' => 'alaagaber',
        'email' => 'alaa@example.com',
        'password' => null,
        'legacy_password' => md5('old-secret'),
        'role' => 'admin',
        'is_active' => true,
    ], $attributes));
}

it('lets a migrated member in with their old password, by username', function () {
    legacyMember();

    $this->post(route('admin.login.store'), ['login' => 'alaagaber', 'password' => 'old-secret'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticated();
});

it('lets a migrated member in by email too', function () {
    legacyMember();

    $this->post(route('admin.login.store'), ['login' => 'alaa@example.com', 'password' => 'old-secret'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticated();
});

it('upgrades the md5 hash to bcrypt on first login', function () {
    $user = legacyMember();

    $this->post(route('admin.login.store'), ['login' => 'alaagaber', 'password' => 'old-secret']);

    $user->refresh();
    expect($user->legacy_password)->toBeNull()
        ->and(Hash::check('old-secret', $user->password))->toBeTrue()
        ->and($user->last_login_at)->not->toBeNull();
});

it('keeps working with the same password after the upgrade', function () {
    legacyMember();

    $this->post(route('admin.login.store'), ['login' => 'alaagaber', 'password' => 'old-secret']);
    $this->post(route('admin.logout'));
    $this->assertGuest();

    $this->post(route('admin.login.store'), ['login' => 'alaagaber', 'password' => 'old-secret'])
        ->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticated();
});

it('accepts old passwords that contain quotes', function () {
    legacyMember(['legacy_password' => md5(addslashes("it's"))]);

    $this->post(route('admin.login.store'), ['login' => 'alaagaber', 'password' => "it's"]);

    $this->assertAuthenticated();
});

it('rejects a wrong password and keeps the old hash', function () {
    legacyMember();

    $this->post(route('admin.login.store'), ['login' => 'alaagaber', 'password' => 'wrong'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
    expect(User::first()->legacy_password)->toBe(md5('old-secret'));
});

it('keeps a disabled account out', function () {
    legacyMember(['is_active' => false]);

    $this->post(route('admin.login.store'), ['login' => 'alaagaber', 'password' => 'old-secret'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('throttles repeated failures', function () {
    legacyMember();

    foreach (range(1, 5) as $_) {
        $this->post(route('admin.login.store'), ['login' => 'alaagaber', 'password' => 'wrong']);
    }

    $this->post(route('admin.login.store'), ['login' => 'alaagaber', 'password' => 'old-secret'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('sends guests to the login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
});

it('shows the dashboard after login', function () {
    $this->actingAs(legacyMember())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(__('admin.dashboard.latest_news'));
});

it('logs out an account disabled during its session', function () {
    $user = legacyMember();
    $this->actingAs($user);
    $user->forceFill(['is_active' => false])->save();

    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    $this->assertGuest();
});
