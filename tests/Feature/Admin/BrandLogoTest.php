<?php

use App\Models\Setting;
use App\Models\User;
use App\Support\Media;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->root = sys_get_temp_dir().'/alnajat-logo-'.uniqid();
    File::ensureDirectoryExists($this->root);
    config(['alnajat.uploads.root' => $this->root]);

    $this->admin = User::forceCreate(['name' => 'المدير', 'username' => 'boss', 'email' => 'boss@example.com',
        'password' => 'secret-123', 'role' => 'admin', 'is_active' => true]);
});

it('shows the site logo from the settings everywhere in the panel', function () {
    File::put($this->root.'/logo_1.png', 'png');
    Setting::put(['site_logo' => 'upload/logo_1.png']);
    $logo = Media::url('upload/logo_1.png');

    // صفحة الدخول: جانب الصفحة، وبطاقة الجوال (على شريحة بلون الثيم)
    $this->get(route('admin.login'))->assertOk()
        ->assertSee('src="'.$logo.'"', false)
        ->assertSee('brand-logo brand-logo-lg is-chip', false)
        ->assertDontSee('brand-fallback', false);

    // القائمة الجانبية وترويسة الجوال
    $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
        ->assertSee('brand-logo brand-logo-md', false)
        ->assertSee('brand-logo brand-logo-sm is-chip', false)
        ->assertSee('alt="'.__('admin.brand').'"', false);

    // صفحات الأخطاء داخل اللوحة
    $this->get('/cp/no-such-page')->assertNotFound()->assertSee('src="'.$logo.'"', false);
    $this->get('/no-such-page')->assertNotFound()->assertDontSee('src="'.$logo.'"', false);
});

it('falls back to the icon and the name when no logo is uploaded or its file is missing', function (string $logo) {
    Setting::put(['site_logo' => $logo]);

    $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
        ->assertSee('brand-fallback', false)
        ->assertSee(__('admin.brand'))
        ->assertDontSee('class="brand-logo', false);
})->with(['', 'upload/not-moved-yet.png']);

afterEach(fn () => File::deleteDirectory($this->root));
