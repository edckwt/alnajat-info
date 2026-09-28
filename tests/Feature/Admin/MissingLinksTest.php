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

it('keeps the ip of the last visitor of each missing link and shows it in the panel', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->get('old-page')->assertNotFound();
    expect(MissingLink::where('path', '/old-page')->value('ip'))->toBe('203.0.113.7');

    $this->withServerVariables(['REMOTE_ADDR' => '2001:db8::1'])->get('old-page')->assertNotFound();
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.4'])->get('other-page')->assertNotFound();

    $link = MissingLink::where('path', '/old-page')->sole();
    expect($link->ip)->toBe('2001:db8::1')->and($link->hits)->toBe(2);

    $this->actingAs($this->admin)->get(route('admin.missing-links.index'))->assertOk()
        ->assertSee('IP (آخر طلب)')
        ->assertSee('2001:db8::1')
        ->assertSee('198.51.100.4');

    // التصفية بعنوان واحد، والبحث يشمل IP
    $this->get(route('admin.missing-links.index', ['ip' => '198.51.100.4']))->assertOk()
        ->assertSee('/other-page')->assertDontSee('/old-page');
    $this->get(route('admin.missing-links.index', ['q' => '2001:db8']))->assertOk()
        ->assertSee('/old-page')->assertDontSee('/other-page');
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
