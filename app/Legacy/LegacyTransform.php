<?php

namespace App\Legacy;

use DateTimeImmutable;
use DateTimeZone;

/**
 * تحويل القيم من شكلها في القاعدة القديمة إلى أعمدة القاعدة الجديدة.
 *
 * يُستخدم في الاستيراد وفي التحقق معاً، حتى يقارن التحقق بنفس القواعد
 * التي نقل بها الاستيراد. لا يعتمد على Laravel.
 */
final class LegacyTransform
{
    private string $hostPattern;

    /** @var array<int,int> */
    private array $pdfVersions;

    /**
     * @param  list<string>  $legacyHosts  مثل ['alnajat.info', 'www.alnajat.info']
     * @param  array<int,int>  $pdfVersions  أعلى رقم نشرة => رقم النسخة
     */
    public function __construct(
        array $legacyHosts,
        array $pdfVersions,
        private readonly int $pdfLatestVersion,
        private readonly string $timezone = 'Asia/Kuwait',
    ) {
        // كل نطاق بصيغتيه (مع www وبدونها) حتى لو ذُكرت واحدة فقط في LEGACY_HOSTS
        $hosts = [];
        foreach ($legacyHosts as $host) {
            $host = preg_replace('#^www\.#', '', strtolower(trim((string) $host)));
            if ($host !== '') {
                array_push($hosts, preg_quote($host, '#'), preg_quote('www.'.$host, '#'));
            }
        }
        $hosts = array_values(array_unique($hosts));
        $this->hostPattern = $hosts === []
            ? '#^$#'
            : '#^https?://(?:'.implode('|', $hosts).')/+#i';

        ksort($pdfVersions);
        $this->pdfVersions = $pdfVersions;
    }

    /** رقم Unix مخزّن كنص (مثل '1557913294') → 'Y-m-d H:i:s' بتوقيت التطبيق. */
    public function timestamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || ! ctype_digit($value) || (int) $value <= 0) {
            return null;
        }

        return (new DateTimeImmutable('@'.$value))
            ->setTimezone(new DateTimeZone($this->timezone))
            ->format('Y-m-d H:i:s');
    }

    /** 'YYYY-MM-DD' صالح → نفسه، وأي شيء آخر → null. */
    public function date(mixed $value): ?string
    {
        $value = trim((string) $value);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) !== 1) {
            return null;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
    }

    public function bool(mixed $value): int
    {
        return (int) $value !== 0 ? 1 : 0;
    }

    public function int(mixed $value): int
    {
        return max(0, (int) $value);
    }

    public function string(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * رابط ملف على الموقع القديم → مسار نسبي.
     * 'http://www.alnajat.info/upload/news_1.png' → 'upload/news_1.png'
     * الروابط الخارجية تبقى كما هي.
     */
    public function localPath(mixed $value): ?string
    {
        $value = $this->string($value);

        if ($value === null) {
            return null;
        }

        return preg_replace($this->hostPattern, '', $value) ?? $value;
    }

    /** قيمة إعداد: الشعار يصبح مساراً نسبياً، وباقي القيم كما هي. */
    public function settingValue(string $key, mixed $value): mixed
    {
        return $key === 'site_logo' ? ($this->localPath($value) ?? '') : $value;
    }

    /** نسخة قالب الـ PDF لنشرة، بنفس منطق pdfVersion() القديم. */
    public function pdfVersion(int $publicationId): int
    {
        foreach ($this->pdfVersions as $maxId => $version) {
            if ($publicationId <= $maxId) {
                return $version;
            }
        }

        return $this->pdfLatestVersion;
    }

    /** user_group القديم → الدور الجديد. 0 كان يعني صلاحية كاملة. */
    public function role(mixed $group): string
    {
        return (int) $group === 0 ? 'admin' : 'editor';
    }

    /** بصمة ثابتة لنص (تُستخدم في المقارنة بين القاعدتين). */
    public function fingerprint(mixed ...$values): string
    {
        return md5(implode("\x1f", array_map(fn ($v) => (string) $v, $values)));
    }
}
