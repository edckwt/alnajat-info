<?php

namespace App\Support;

/** سجل الصلاحيات المعرّفة في config/permissions.php. */
final class Permissions
{
    /** @return array<string, array{label:string, abilities:array<string,string>}> */
    public static function modules(): array
    {
        return config('permissions.modules', []);
    }

    /** @return list<string> كل المفاتيح: news.view، news.create، ... */
    public static function all(): array
    {
        $keys = [];
        foreach (self::modules() as $module => $config) {
            foreach (array_keys($config['abilities']) as $ability) {
                $keys[] = "$module.$ability";
            }
        }

        return $keys;
    }

    public static function exists(string $key): bool
    {
        static $flip = null;
        $flip ??= array_flip(self::all());

        return isset($flip[$key]);
    }

    /**
     * يبقي المفاتيح المعروفة فقط (وبترتيب التعريف)، ويحوّل «*» إلى الكل.
     *
     * @param  iterable<string>|null  $keys
     * @return list<string>
     */
    public static function normalize(?iterable $keys): array
    {
        $keys = collect($keys ?? [])->map(fn ($k) => (string) $k);

        if ($keys->contains('*')) {
            return self::all();
        }

        return array_values(array_intersect(self::all(), $keys->all()));
    }

    public static function label(string $key): string
    {
        [$module, $ability] = array_pad(explode('.', $key, 2), 2, '');
        $config = self::modules()[$module] ?? null;

        return $config ? $config['label'].' — '.($config['abilities'][$ability] ?? $ability) : $key;
    }
}
