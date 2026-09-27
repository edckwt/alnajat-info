<?php

namespace App\Legacy;

/**
 * مطابقة كلمات المرور المحفوظة بواسطة النظام القديم.
 *
 * النظام القديم حضّر كلمة المرور بطريقتين مختلفتين قبل MD5:
 *
 *  - عند الدخول (class-cp.php، login):
 *        md5( addslashes( trim($password) ) )
 *  - عند إنشاء الحساب أو تغيير كلمة المرور (user_insert / user_update):
 *        md5( mysqli_real_escape_string( trim($password) ) )
 *
 * الدالتان تعطيان نفس الناتج لأغلب كلمات المرور، وتختلفان فقط إن احتوت
 * على سطر جديد أو \r أو \x00 أو \x1a. نقبل الصيغتين حتى لا يُحرم أي عضو
 * من الدخول بكلمة مروره الحالية.
 *
 * لا يعتمد هذا الصنف على Laravel، فيُختبر مستقلاً.
 */
final class LegacyPassword
{
    public static function looksLegacy(?string $hash): bool
    {
        return is_string($hash) && preg_match('/^[a-f0-9]{32}$/i', $hash) === 1;
    }

    public static function matches(string $plain, ?string $storedMd5): bool
    {
        if (! self::looksLegacy($storedMd5)) {
            return false;
        }

        $stored = strtolower($storedMd5);

        foreach (self::candidates($plain) as $candidate) {
            if (hash_equals($stored, md5($candidate))) {
                return true;
            }
        }

        return false;
    }

    /**
     * كل الصيغ التي قد يكون النظام القديم مرّر بها كلمة المرور إلى md5().
     *
     * @return list<string>
     */
    public static function candidates(string $plain): array
    {
        $trimmed = trim($plain);

        return array_values(array_unique([
            addslashes($trimmed),
            self::mysqliEscape($trimmed),
        ]));
    }

    /**
     * نفس ما يفعله mysqli_real_escape_string مع ترميز utf8، بدون اتصال بقاعدة.
     */
    public static function mysqliEscape(string $value): string
    {
        return strtr($value, [
            '\\' => '\\\\',
            "\0" => '\\0',
            "\n" => '\\n',
            "\r" => '\\r',
            "'" => "\\'",
            '"' => '\\"',
            "\x1a" => '\\Z',
        ]);
    }
}
