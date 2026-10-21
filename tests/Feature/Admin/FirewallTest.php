<?php

use App\Models\BlockedIp;
use App\Models\Setting;
use App\Models\User;
use App\Support\Firewall;

/** يغيّر عنوان الطلبات التالية في الاختبار (withServerVariables يبقى حتى يُغيَّر). */
function fromIp(string $ip, array $extra = []): array
{
    return ['REMOTE_ADDR' => $ip] + $extra;
}

beforeEach(function () {
    $this->admin = User::forceCreate(['name' => 'المدير', 'username' => 'boss', 'email' => 'boss@example.com',
        'password' => 'secret-123', 'role' => 'admin', 'is_active' => true]);
});

it('blocks an ip that requests a trap link like /.git-credentials, and only that ip', function () {
    $this->withServerVariables(fromIp('203.0.113.66'))->get('/home/user/.git-credentials')
        ->assertForbidden()->assertSee('تم حظر الوصول')->assertSee('203.0.113.66');

    $row = BlockedIp::where('ip', '203.0.113.66')->sole();
    expect($row->reason)->toBe('trap')
        ->and($row->trigger)->toBe('/home/user/.git-credentials')
        ->and($row->strikes)->toBe(1)
        ->and($row->blocked_until->between(now()->addHours(23), now()->addHours(25)))->toBeTrue();

    // كل الموقع مرفوض له بعدها، ولغيره يعمل
    $this->get('/')->assertForbidden();
    $this->get('/cp/login')->assertForbidden();
    expect(BlockedIp::where('ip', '203.0.113.66')->value('hits'))->toBeGreaterThan(0);

    $this->withServerVariables(fromIp('198.51.100.9'))->get('/')->assertOk();

    foreach (['/.env', '/wp-login.php', '/phpmyadmin/index.php', '/.git/config', '/x?file=../../etc/passwd', '/%2e%2e%2fetc/passwd'] as $i => $trap) {
        $ip = '192.0.2.'.($i + 10);
        $this->withServerVariables(fromIp($ip))->get($trap)->assertForbidden();
        expect(Firewall::isBlocked($ip))->toBeTrue();
    }
});

it('never blocks localhost, the allowlist, or the old site links', function () {
    $this->get('/.env')->assertNotFound(); // 127.0.0.1 مستثنى دائماً
    expect(BlockedIp::count())->toBe(0);

    Setting::put(['firewall_allowlist' => "203.0.113.0/24\n2001:db8::/32"]);
    $this->withServerVariables(fromIp('203.0.113.200'))->get('/.env')->assertNotFound();
    $this->withServerVariables(fromIp('2001:db8::5'))->get('/wp-login.php')->assertNotFound();

    // روابط الموقع القديم والصفحات العادية لا تدخل المصيدة
    $this->withServerVariables(fromIp('198.51.100.20'))->get('/index.php?action=show&id=999999');
    $this->get('/upload/news_1.jpg');
    $this->get('/category/99999');
    expect(BlockedIp::count())->toBe(0);
});

it('blocks too many missing pages in a short time, but not images or search engines', function () {
    Setting::put(['firewall_not_found_limit' => '5', 'firewall_not_found_minutes' => '5']);

    // صور مكسورة في خبر قديم وزحف محركات البحث لا تُحتسب
    $this->withServerVariables(fromIp('198.51.100.30'));
    foreach (range(1, 8) as $i) {
        $this->get("/upload/missing-$i.jpg")->assertNotFound();
    }
    $this->withServerVariables(fromIp('198.51.100.31', ['HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1)']));
    foreach (range(1, 8) as $i) {
        $this->get("/old-page-$i")->assertNotFound();
    }
    expect(BlockedIp::count())->toBe(0);

    $this->withServerVariables(fromIp('198.51.100.32', ['HTTP_USER_AGENT' => 'curl/8']));
    foreach (range(1, 5) as $i) {
        $this->get("/probe-$i")->assertNotFound();
    }
    $this->get('/')->assertForbidden();
    expect(BlockedIp::where('ip', '198.51.100.32')->value('reason'))->toBe('flood');
});

it('blocks longer each time the same ip comes back', function () {
    Firewall::block('203.0.113.77', 'trap', '/.env');
    expect(BlockedIp::sole()->blocked_until->diffInHours(now(), true))->toBeGreaterThan(23);

    $this->travel(2)->days();
    expect(Firewall::isBlocked('203.0.113.77'))->toBeFalse();

    $this->withServerVariables(fromIp('203.0.113.77'))->get('/.git/HEAD')->assertForbidden();
    $row = BlockedIp::sole();
    expect($row->strikes)->toBe(2)->and($row->blocked_until->diffInDays(now(), true))->toBeGreaterThan(6);

    $this->travel(8)->days();
    Firewall::block('203.0.113.77', 'trap', '/.env');
    expect(BlockedIp::sole()->strikes)->toBe(3)->and(BlockedIp::sole()->blocked_until->diffInDays(now(), true))->toBeGreaterThan(29);
});

it('manages blocked ips and the settings from the panel', function () {
    Firewall::block('203.0.113.88', 'trap', '/.env');
    $this->withServerVariables(fromIp('127.0.0.1'));

    $this->actingAs($this->admin)->get(route('admin.firewall.index'))->assertOk()
        ->assertSee('203.0.113.88')->assertSee('رابط مصيدة')->assertSee('/.env')
        ->assertSee('127.0.0.1');                                            // عنوانك الحالي

    // حظر يدوي، ولا يمكن حظر نفسك ولا عنوان مستثنى
    $this->post(route('admin.firewall.store'), ['ip' => '198.51.100.40', 'duration' => 'permanent', 'note' => 'سبام'])->assertSessionHasNoErrors();
    expect(BlockedIp::where('ip', '198.51.100.40')->value('blocked_until'))->toBeNull();
    $this->post(route('admin.firewall.store'), ['ip' => '127.0.0.1'])->assertSessionHasErrors('ip');
    $this->post(route('admin.firewall.store'), ['ip' => 'not-an-ip'])->assertSessionHasErrors('ip');

    // رفع الحظر يبقي السجل، والحذف يزيله
    $row = BlockedIp::where('ip', '203.0.113.88')->sole();
    $this->patch(route('admin.firewall.unblock', $row))->assertRedirect();
    expect(Firewall::isBlocked('203.0.113.88'))->toBeFalse()->and(BlockedIp::where('ip', '203.0.113.88')->exists())->toBeTrue();
    $this->delete(route('admin.firewall.destroy', $row))->assertRedirect();
    expect(BlockedIp::where('ip', '203.0.113.88')->exists())->toBeFalse();

    // الإعدادات: نمط يطابق رابطاً حقيقياً أو استثناء غير صحيح يُرفض
    $this->put(route('admin.firewall.settings'), ['enabled' => 1, 'trap_paths' => "/.env\n/show", 'allowlist' => '', 'not_found_limit' => 30, 'not_found_minutes' => 5])
        ->assertSessionHasErrors('trap_paths');
    $this->put(route('admin.firewall.settings'), ['enabled' => 1, 'trap_paths' => '', 'allowlist' => '10.0.0.0/99', 'not_found_limit' => 30, 'not_found_minutes' => 5])
        ->assertSessionHasErrors('allowlist');
    $this->put(route('admin.firewall.settings'), [
        'enabled' => 1, 'trap_paths' => implode("\n", config('alnajat.firewall.trap_paths')), 'allowlist' => "198.51.100.0/24", 'not_found_limit' => 40, 'not_found_minutes' => 10,
    ])->assertSessionHasNoErrors();
    expect(Setting::get('firewall_trap_paths'))->toBe('')                  // الافتراضية كما هي
        ->and(Firewall::isAllowed('198.51.100.40'))->toBeTrue()
        ->and(Firewall::notFoundLimit())->toBe(40);

    // الإيقاف: لا حظر ولا رفض
    $this->put(route('admin.firewall.settings'), ['enabled' => 0, 'trap_paths' => '', 'allowlist' => '', 'not_found_limit' => 30, 'not_found_minutes' => 5]);
    $this->withServerVariables(fromIp('192.0.2.99'))->get('/.env')->assertNotFound();
    expect(Firewall::isBlocked('192.0.2.99'))->toBeFalse();
});

it('offers blocking from the missing links page, and limits the panel to its permissions', function () {
    $this->withServerVariables(fromIp('198.51.100.50'))->get('/old-page')->assertNotFound();
    $this->withServerVariables(fromIp('127.0.0.1'));

    $this->actingAs($this->admin)->get(route('admin.missing-links.index'))->assertOk()
        ->assertSee(route('admin.firewall.store'), false)->assertSee('حظر 198.51.100.50 يوماً كاملاً؟');

    $editor = User::forceCreate(['name' => 'محرر', 'username' => 'writer', 'email' => 'writer@example.com',
        'password' => 'secret-123', 'role' => 'editor', 'is_active' => true]);
    $this->actingAs($editor)->get(route('admin.firewall.index'))->assertForbidden();
    $this->post(route('admin.firewall.store'), ['ip' => '198.51.100.50'])->assertForbidden();
});

it('can unblock from the command line if you lock yourself out', function () {
    Firewall::block('203.0.113.99', 'manual', null, null);

    $this->artisan('firewall:unblock', ['--list' => true])->assertSuccessful()->expectsOutputToContain('203.0.113.99');
    $this->artisan('firewall:unblock', ['ip' => '203.0.113.99'])->assertSuccessful();
    expect(Firewall::isBlocked('203.0.113.99'))->toBeFalse();
});
