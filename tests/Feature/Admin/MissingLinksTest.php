<?php

use App\Models\MissingLink;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::forceCreate(['name' => 'المدير', 'username' => 'admin', 'email' => 'admin@example.com',
        'password' => 'secret-123', 'role' => 'admin', 'is_active' => true]);
});

it('records every 404 once and counts repeat visits', function () {
    $this->get('show/999999')->assertNotFound();
    $this->withHeader('referer', 'https://wa.me/x')->get('show/999999')->assertNotFound();
    $this->get('no-such-page.html')->assertNotFound();

    $link = MissingLink::where('path', '/show/999999')->sole();

    expect($link->hits)->toBe(2)
        ->and($link->referer)->toBe('https://wa.me/x')
        ->and(MissingLink::count())->toBe(2);
});

it('does not record the control panel or working pages', function () {
    $this->get('/')->assertOk();
    $this->actingAs($this->admin)->get('cp/no-such-page')->assertNotFound();

    expect(MissingLink::count())->toBe(0);
});

it('lists, deletes and clears missing links for admins only', function () {
    $this->get('old-page-1')->assertNotFound();
    $this->get('old-page-2')->assertNotFound();

    $this->actingAs($this->admin)->get(route('admin.missing-links.index'))
        ->assertOk()->assertSee('/old-page-1')->assertSee('/old-page-2');

    $this->delete(route('admin.missing-links.destroy', MissingLink::where('path', '/old-page-1')->sole()));
    expect(MissingLink::count())->toBe(1);

    $this->delete(route('admin.missing-links.clear'));
    expect(MissingLink::count())->toBe(0);

    $editor = User::forceCreate(['name' => 'محرر', 'username' => 'editor', 'email' => 'editor@example.com',
        'password' => 'secret-123', 'role' => 'editor', 'is_active' => true]);
    $this->actingAs($editor)->get(route('admin.missing-links.index'))->assertForbidden();
});
