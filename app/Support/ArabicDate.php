<?php

namespace App\Support;

use DateTimeInterface;

/** التاريخ بالعربية كما في day_name() القديمة: «الجمعة 25 سبتمبر 2026». */
final class ArabicDate
{
    private const DAYS = ['السبت' => 6, 'الأحد' => 0, 'الإثنين' => 1, 'الثلاثاء' => 2, 'الأربعاء' => 3, 'الخميس' => 4, 'الجمعة' => 5];

    private const MONTHS = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

    public static function long(DateTimeInterface $date): string
    {
        $day = array_search((int) $date->format('w'), self::DAYS, true);

        return $day.' '.$date->format('j').' '.self::MONTHS[(int) $date->format('n')].' '.$date->format('Y');
    }

    /** '2023-11' أو تاريخ كامل → «نوفمبر 2023». */
    public static function monthYear(?string $date): ?string
    {
        if (! $date || ! preg_match('/^(\d{4})-(\d{2})/', $date, $m)) {
            return null;
        }

        return (self::MONTHS[(int) $m[2]] ?? '').' '.$m[1];
    }

    public static function dayName(DateTimeInterface $date): string
    {
        return (string) array_search((int) $date->format('w'), self::DAYS, true);
    }

    /** التاريخ الهجري (أم القرى) إن توفرت إضافة intl، وإلا نص فارغ. */
    public static function hijri(DateTimeInterface $date): string
    {
        if (! class_exists(\IntlDateFormatter::class)) {
            return '';
        }

        $formatter = new \IntlDateFormatter('ar_SA@calendar=islamic-umalqura', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE,
            $date->getTimezone(), \IntlDateFormatter::TRADITIONAL, 'd MMMM y هـ');

        // أرقام عربية غربية (0-9) كبقية النشرة
        return strtr((string) $formatter->format($date), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    }
}
