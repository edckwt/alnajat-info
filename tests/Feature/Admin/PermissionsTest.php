<?php

use App\Models\Category;
use App\Models\News;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;

function member(string $role = 'editor', array $permissions = [], string $username = 'member'): User
{
    return User::forceCreate([
        'name' => 'عضو '.$username, 'username' => $username, 'email' => "$username@example.com",
        'password' => 'secret-123', 'role' => $role, 'permissions' => $permissions, 'is_active' => true,
    ]);
}

beforeEach(function () {
    Category::forceCreate(['id' => 1, 'name' => 'محليات', 'is_active' => true]);
    $this->admin = member('admin', [], 'admin');
});

it('seeds the system roles so existing members keep their access', function () {
    expect(Role::pluck('key')->all())->toContain('admin', 'editor');

    $editor = member();
    expect($editor->hasPermission('news.create'))->toBeTrue()
        ->and($editor->hasPermission('users.view'))->toBeFalse()
        ->and($this->admin->hasPermission('roles.delete'))->toBeTrue();
});

it('protects every route with its permission', function () {
    Role::forceCreate(['key' => 'reader', 'name' => 'قارئ', 'permissions' => ['news.view']]);
    $reader = member('reader');
    $news = News::forceCreate(['title' => 'خبر', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25']);

    $this->actingAs($reader);
    $this->get(route('admin.news.index'))->assertOk();
    $this->get(route('admin.news.create'))->assertForbidden();
    $this->get(route('admin.news.edit', $news))->assertForbidden();
    $this->patch(route('admin.news.toggle', $news))->assertForbidden();
    $this->delete(route('admin.news.destroy', $news))->assertForbidden();
    $this->get(route('admin.news.order'))->assertForbidden();
    $this->get(route('admin.categories.index'))->assertForbidden();
    $this->get(route('admin.users.index'))->assertForbidden()->assertSee('لا تملك صلاحية');
    $this->get(route('admin.roles.index'))->assertForbidden();
    $this->get(route('admin.settings.edit'))->assertForbidden();

    // القائمة الجانبية تعرض ما يملكه فقط
    $this->get(route('admin.dashboard'))->assertOk()
        ->assertSee(route('admin.news.index'), false)
        ->assertDontSee(route('admin.categories.index'), false)
        ->assertDontSee(route('admin.users.index'), false);
});

it('limits members without manage_all to their own news', function () {
    Role::forceCreate(['key' => 'writer', 'name' => 'كاتب', 'permissions' => ['news.view', 'news.create', 'news.update', 'news.delete']]);
    $writer = member('writer');
    $mine = News::forceCreate(['title' => 'خبري', 'type' => 1, 'is_active' => false, 'published_date' => '2026-09-25', 'created_by' => $writer->id]);
    $theirs = News::forceCreate(['title' => 'خبر غيري', 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25', 'created_by' => $this->admin->id]);

    $this->actingAs($writer);
    $this->get(route('admin.news.edit', $mine))->assertOk();
    $this->get(route('admin.news.edit', $theirs))->assertForbidden();
    $this->delete(route('admin.news.destroy', $theirs))->assertForbidden();

    // بلا صلاحية النشر: الخبر الجديد يُحفظ مخفياً حتى لو طُلب نشره
    $this->post(route('admin.news.store'), [
        'type' => 1, 'title' => 'خبر جديد', 'published_date' => '2026-09-25', 'is_active' => '1', 'categories' => [1],
    ])->assertSessionHasNoErrors();

    expect(News::where('title', 'خبر جديد')->sole()->is_active)->toBeFalse();
});

it('grants extra permissions to one member on top of the role', function () {
    Setting::put(['site_title' => 'قديم']);
    $member = member('editor', ['settings.home']);

    $this->actingAs($member);
    $this->get(route('admin.settings.edit'))->assertOk()
        ->assertSee('صناديق الصفحة الرئيسية')
        ->assertDontSee('name="setting[site_title]"', false);

    // مفاتيح تبويب لا يملكه تُتجاهل
    $this->put(route('admin.settings.update'), [
        'tab' => 'home',
        'setting' => ['site_title' => 'مخترق'],
        'boxes' => ['home' => [1 => ['kind' => 'news', 'position' => 1, 'category_id' => 1, 'items_limit' => 3, 'type' => 1]]],
    ])->assertRedirect();

    expect(Setting::get('site_title'))->toBe('قديم')
        ->and(\App\Models\HomeBox::where('context', 'home')->sole()->category_id)->toBe(1);
});

it('manages roles and never lets a member grant more than they have', function () {
    // المدير ينشئ دوراً
    $this->actingAs($this->admin)->post(route('admin.roles.store'), [
        'name' => 'مشرف أخبار', 'description' => 'الأخبار فقط', 'permissions' => ['news.view', 'news.update', 'users.view', 'users.update', 'roles.view', 'roles.update'],
    ])->assertRedirect(route('admin.roles.index'));

    $role = Role::where('name', 'مشرف أخبار')->sole();
    expect($role->key)->toStartWith('role-')
        ->and($role->permissions)->toBe(['news.view', 'news.update', 'users.view', 'users.update', 'roles.view', 'roles.update']);

    // عضو بهذا الدور يحاول منح دور آخر صلاحية لا يملكها، ومنح دور المدير
    $manager = member($role->key, [], 'manager');
    $target = member('editor', [], 'target');
    $other = Role::forceCreate(['key' => 'other', 'name' => 'دور آخر', 'permissions' => ['settings.pdf']]);

    $this->actingAs($manager);
    $this->put(route('admin.roles.update', $other), ['name' => 'دور آخر', 'permissions' => ['news.view', 'settings.general']])
        ->assertRedirect(route('admin.roles.index'));
    // news.view يملكها فمنحها، settings.general لا يملكها فتجاهلها، و settings.pdf ليست له فبقيت كما هي
    expect($other->fresh()->permissions)->toBe(['news.view', 'settings.pdf']);

    $this->put(route('admin.users.update', $target), [
        'name' => 'هدف', 'username' => 'target', 'role' => 'admin', 'is_active' => '1',
    ])->assertSessionHasErrors('role');

    $this->put(route('admin.users.update', $target), [
        'name' => 'هدف', 'username' => 'target', 'role' => 'editor', 'permissions' => ['news.view', 'settings.pdf'], 'is_active' => '1',
    ])->assertSessionHasNoErrors();
    expect($target->fresh()->permissions)->toBe(['news.view']);

    // لا يعدّل حساب مدير، ولا يحذف دوراً أساسياً
    $this->get(route('admin.users.edit', $this->admin))->assertForbidden();
    $this->get(route('admin.roles.edit', Role::where('key', 'admin')->sole()))->assertForbidden();
});

it('refuses to delete a role that members still use', function () {
    $role = Role::forceCreate(['key' => 'temp', 'name' => 'مؤقت', 'permissions' => ['news.view']]);
    member('temp');

    $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $role))->assertSessionHasErrors('role');
    expect(Role::whereKey($role->id)->exists())->toBeTrue();

    $this->delete(route('admin.roles.destroy', Role::where('key', 'editor')->sole()))->assertSessionHasErrors('role');
});

it('shows the roles and the permission matrix in Arabic', function () {
    $this->actingAs($this->admin);

    $this->get(route('admin.roles.index'))->assertOk()->assertSee('مدير النظام')->assertSee('محرر');
    $this->get(route('admin.roles.create'))->assertOk()->assertSee('تعديل أخبار الآخرين وحذفها')->assertSee('أقسام ملف الـ PDF');
    $this->get(route('admin.users.edit', member()))->assertOk()->assertSee('صلاحيات إضافية لهذا العضو')->assertSee('(الدور)');
});

it('speaks Arabic in validation messages', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), ['name' => ''])
        ->assertSessionHasErrors(['name' => 'حقل اسم القسم مطلوب.']);
});
