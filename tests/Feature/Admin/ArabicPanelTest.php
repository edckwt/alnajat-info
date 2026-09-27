<?php

use App\Models\User;

it('always renders the panel in Arabic and right to left', function () {
    expect(app()->getLocale())->toBe('ar');

    $this->get(route('admin.login'))->assertOk()
        ->assertSee('<html lang="ar" dir="rtl"', false)
        ->assertSee('تسجيل الدخول');

    $admin = User::forceCreate(['name' => 'المدير', 'username' => 'boss', 'email' => 'boss@example.com',
        'password' => 'secret-123', 'role' => 'admin', 'is_active' => true]);

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
        ->assertSee('<html lang="ar" dir="rtl"', false)
        ->assertSee('لوحة التحكم')
        ->assertSee(__('admin.brand'))
        ->assertDontSee('Laravel')
        // منتقي التاريخ والقوائم بالبحث لكل الحقول (مكتبات محلية)
        ->assertSee('admin/js/plugins.js', false)
        ->assertSee('admin/vendor/tom-select/tom-select.complete.min.js', false)
        ->assertSee('admin/vendor/flatpickr/flatpickr.min.js', false);

    foreach (['flatpickr/flatpickr.min.js', 'flatpickr/ar.js', 'flatpickr/flatpickr.min.css', 'tom-select/tom-select.complete.min.js', 'tom-select/tom-select.min.css'] as $file) {
        expect(file_exists(public_path('admin/vendor/'.$file)))->toBeTrue();
    }
});

it('shows validation messages in Arabic', function () {
    $this->post(route('admin.login.store'), [])->assertSessionHasErrors();

    expect(session('errors')->first())->toMatch('/\p{Arabic}/u');
});
