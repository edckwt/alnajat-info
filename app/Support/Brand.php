<?php

namespace App\Support;

use App\Models\Setting;
use Throwable;

/**
 * شعار الموقع من الإعدادات (عام ← الشعار، site_logo) لكل أماكن الشعار في اللوحة:
 * القائمة الجانبية، ترويسة الجوال، صفحة الدخول، وصفحات الأخطاء. بلا شعار: الأيقونة واسم «النجاة».
 */
final class Brand
{
    public static function logoUrl(): ?string
    {
        try {
            $logo = (string) Setting::get('site_logo');
            // صورة مرفوعة غير موجودة على القرص (لم تُنقل بعد): الاسم بدل صورة مكسورة
            if ($logo === '' || (Media::isUpload($logo) && ! is_file((string) Media::path($logo)))) {
                return null;
            }

            return Media::url($logo);
        } catch (Throwable) {
            return null; // صفحة خطأ والقاعدة غير متاحة: يُعرض الاسم
        }
    }

    public static function name(): string
    {
        return (string) __('admin.brand');
    }
}
