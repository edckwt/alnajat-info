<?php

use App\Models\News;
use App\Models\User;
use App\Services\ImageUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->uploadDir = sys_get_temp_dir().'/alnajat-test-profile-'.uniqid();
    $this->app->instance(ImageUploader::class, new ImageUploader($this->uploadDir));

    $this->editor = User::forceCreate([
        'name' => 'سارة العتيبي', 'username' => 'sara', 'email' => 'sara@example.com',
        'password' => 'secret-123', 'role' => 'editor', 'is_active' => true,
    ]);
    $this->other = User::forceCreate([
        'name' => 'عضو آخر', 'username' => 'other', 'email' => 'other@example.com',
        'password' => 'secret-123', 'role' => 'editor', 'is_active' => true,
    ]);
});

afterEach(fn () => File::deleteDirectory($this->uploadDir));

it('opens the profile for any member, with their own stats and news', function () {
    News::forceCreate(['title' => 'خبر سارة', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25', 'created_by' => $this->editor->id]);
    News::forceCreate(['title' => 'خبر غيرها', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25', 'created_by' => $this->other->id]);

    $this->get(route('admin.profile.show'))->assertRedirect(route('admin.login'));

    $this->actingAs($this->editor)
        ->get(route('admin.profile.show'))
        ->assertOk()
        ->assertSee('سارة العتيبي')
        ->assertSee('خبر سارة')
        ->assertDontSee('خبر غيرها')
        ->assertSee('تغيير كلمة المرور');

    $this->get(route('admin.profile.show', ['tab' => 'security']))->assertOk();
});

it('offers the image editor on drag and drop uploads', function () {
    $this->actingAs($this->editor)->get(route('admin.profile.show'))->assertOk()
        ->assertSee('admin/js/image-editor.js', false)
        ->assertSee('data-dropzone', false)
        ->assertSee('data-aspect="1"', false)       // الصورة الشخصية مربعة
        ->assertSee('data-max-width="800"', false); // وتُصغَّر تلقائياً

    expect(file_exists(public_path('admin/vendor/cropper/cropper.min.js')))->toBeTrue();
});

it('updates the member data and avatar with drag and drop upload', function () {
    $this->actingAs($this->editor)->put(route('admin.profile.update'), [
        'name' => 'سارة محمد', 'username' => 'sara.m', 'email' => 'sara.m@example.com',
        'phone' => '+965 5000 1234', 'bio' => 'محررة أخبار',
        'avatar_file' => UploadedFile::fake()->image('me.png', 400, 400),
    ])->assertRedirect(route('admin.profile.show', ['tab' => 'account']));

    $user = $this->editor->fresh();
    expect($user->name)->toBe('سارة محمد')
        ->and($user->username)->toBe('sara.m')
        ->and($user->phone)->toBe('+965 5000 1234')
        ->and($user->bio)->toBe('محررة أخبار')
        ->and($user->avatar)->toStartWith('upload/avatar_')
        ->and($user->role)->toBe('editor'); // الدور لا يتغير من الملف الشخصي

    $this->get(route('admin.profile.show'))->assertSee($user->avatarUrl(), false);

    $this->put(route('admin.profile.update'), ['name' => 'سارة محمد', 'username' => 'sara.m', 'remove_avatar' => '1'])->assertRedirect();
    expect($this->editor->fresh()->avatar)->toBeNull();
});

it('ignores role and permission fields sent to the profile form', function () {
    $this->actingAs($this->editor)->put(route('admin.profile.update'), [
        'name' => 'سارة', 'username' => 'sara', 'role' => 'admin', 'permissions' => ['users.delete'], 'is_active' => 0,
    ])->assertRedirect();

    $user = $this->editor->fresh();
    expect($user->role)->toBe('editor')->and($user->permissions)->toBeNull()->and($user->is_active)->toBeTrue();
});

it('validates unique username and email and the avatar file', function () {
    $this->actingAs($this->editor)->put(route('admin.profile.update'), [
        'name' => 'سارة', 'username' => 'other', 'email' => 'other@example.com',
        'avatar_file' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors(['username', 'email', 'avatar_file']);
});

it('changes the password only with the current one', function () {
    $this->actingAs($this->editor);

    $this->put(route('admin.profile.password'), [
        'current_password' => 'wrong-pass', 'password' => 'new-secret-9', 'password_confirmation' => 'new-secret-9',
    ])->assertSessionHasErrors('current_password');

    $this->put(route('admin.profile.password'), [
        'current_password' => 'secret-123', 'password' => 'short', 'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');

    $this->put(route('admin.profile.password'), [
        'current_password' => 'secret-123', 'password' => 'new-secret-9', 'password_confirmation' => 'new-secret-9',
    ])->assertRedirect(route('admin.profile.show', ['tab' => 'security']));

    expect(Hash::check('new-secret-9', $this->editor->fresh()->password))->toBeTrue();
    $this->assertAuthenticatedAs($this->editor);
});

it('lets an admin set a member avatar and phone from the members page', function () {
    $admin = User::forceCreate(['name' => 'المدير', 'username' => 'boss', 'email' => 'boss@example.com',
        'password' => 'secret-123', 'role' => 'admin', 'is_active' => true]);

    $this->actingAs($admin)->put(route('admin.users.update', $this->editor), [
        'name' => 'سارة العتيبي', 'username' => 'sara', 'email' => 'sara@example.com', 'phone' => '55001234',
        'role' => 'editor', 'is_active' => 1,
        'avatar_file' => UploadedFile::fake()->image('sara.jpg', 300, 300),
    ])->assertRedirect(route('admin.users.index'));

    $user = $this->editor->fresh();
    expect($user->avatar)->toStartWith('upload/avatar_')->and($user->phone)->toBe('55001234');

    $this->get(route('admin.users.index'))->assertOk()->assertSee($user->avatarUrl(), false);
});
