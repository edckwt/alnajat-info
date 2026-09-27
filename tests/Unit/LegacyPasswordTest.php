<?php

use App\Legacy\LegacyPassword;

// كيف كان النظام القديم يحفظ كلمة المرور عند إنشاء الحساب (mysqli_real_escape_string)
// وعند الدخول (addslashes). النتيجتان تختلفان فقط مع \n و \r و \0 و \x1a.
function legacyCreateHash(string $plain): string
{
    return md5(LegacyPassword::mysqliEscape(trim($plain)));
}

function legacyLoginHash(string $plain): string
{
    return md5(addslashes(trim($plain)));
}

it('accepts the password a member has today', function (string $plain) {
    expect(LegacyPassword::matches($plain, legacyCreateHash($plain)))->toBeTrue()
        ->and(LegacyPassword::matches($plain, legacyLoginHash($plain)))->toBeTrue();
})->with([
    'simple' => 'secret123',
    'arabic' => 'كلمة-سر ١٢٣',
    'single quote' => "it's",
    'double quote' => 'say "hi"',
    'backslash' => 'back\\slash',
    'newline' => "two\nlines",
    'surrounding spaces' => '  padded  ',
]);

it('ignores surrounding spaces like the old system did', function () {
    expect(LegacyPassword::matches(' secret ', legacyCreateHash('secret')))->toBeTrue();
});

it('rejects a wrong password', function () {
    expect(LegacyPassword::matches('secret1', legacyCreateHash('secret')))->toBeFalse();
});

it('only treats 32 hex characters as a legacy hash', function () {
    expect(LegacyPassword::looksLegacy(md5('x')))->toBeTrue()
        ->and(LegacyPassword::looksLegacy(password_hash('x', PASSWORD_BCRYPT)))->toBeFalse()
        ->and(LegacyPassword::looksLegacy(null))->toBeFalse()
        ->and(LegacyPassword::matches('x', null))->toBeFalse();
});

it('escapes exactly like mysqli_real_escape_string', function () {
    expect(LegacyPassword::mysqliEscape("a'b\"c\\d\ne\rf\0g\x1a"))
        ->toBe("a\\'b\\\"c\\\\d\\ne\\rf\\0g\\Z");
});
