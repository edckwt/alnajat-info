<?php

/*
 * صلاحيات لوحة التحكم: وحدة => [الاسم، العمليات].
 * المفتاح الكامل «الوحدة.العملية» (news.create) هو ما يُسند للأدوار وللأعضاء،
 * وهو نفسه اسم الـ Gate: @can('news.create') و ->middleware('can:news.create').
 * دور «مدير النظام» (admin) يملك كل شيء دائماً.
 */
return [
    'modules' => [
        'news' => [
            'label' => 'الأخبار',
            'abilities' => [
                'view' => 'عرض القائمة',
                'create' => 'إضافة',
                'update' => 'تعديل',
                'delete' => 'حذف',
                'publish' => 'نشر وإخفاء',
                'order' => 'ترتيب أخبار اليوم',
                'manage_all' => 'تعديل أخبار الآخرين وحذفها',
            ],
        ],
        'publications' => [
            'label' => 'النشرات',
            'abilities' => [
                'view' => 'عرض ومعاينة',
                'create' => 'إضافة',
                'update' => 'تعديل',
                'delete' => 'حذف',
                'publish' => 'نشر وإخفاء',
            ],
        ],
        'categories' => [
            'label' => 'الأقسام',
            'abilities' => ['view' => 'عرض', 'create' => 'إضافة', 'update' => 'تعديل', 'delete' => 'حذف', 'publish' => 'إظهار وإخفاء'],
        ],
        'newspapers' => [
            'label' => 'الصحف',
            'abilities' => ['view' => 'عرض', 'create' => 'إضافة', 'update' => 'تعديل', 'delete' => 'حذف', 'publish' => 'إظهار وإخفاء'],
        ],
        'banners' => [
            'label' => 'البانرات',
            'abilities' => ['view' => 'عرض', 'create' => 'إضافة', 'update' => 'تعديل', 'delete' => 'حذف', 'publish' => 'تفعيل وإيقاف'],
        ],
        'uploads' => [
            'label' => 'الملفات',
            'abilities' => ['view' => 'عرض', 'create' => 'رفع', 'delete' => 'حذف'],
        ],
        'pdf_templates' => [
            'label' => 'قوالب النشرة',
            'abilities' => ['view' => 'عرض ومعاينة', 'create' => 'إنشاء ونسخ', 'update' => 'تصميم وتعديل', 'delete' => 'حذف', 'publish' => 'اعتماد القالب'],
        ],
        'settings' => [
            'label' => 'الإعدادات',
            'abilities' => [
                'general' => 'الإعدادات العامة',
                'home' => 'صناديق الصفحة الرئيسية',
                'pdf' => 'إعدادات ملف الـ PDF',
                'pdf_boxes' => 'أقسام ملف الـ PDF',
            ],
        ],
        'users' => [
            'label' => 'الأعضاء',
            'abilities' => ['view' => 'عرض', 'create' => 'إضافة', 'update' => 'تعديل وإسناد الأدوار', 'delete' => 'حذف'],
        ],
        'roles' => [
            'label' => 'الأدوار والصلاحيات',
            'abilities' => ['view' => 'عرض', 'create' => 'إضافة', 'update' => 'تعديل', 'delete' => 'حذف'],
        ],
        'missing_links' => [
            'label' => 'الروابط المفقودة',
            'abilities' => ['view' => 'عرض', 'delete' => 'حذف وتفريغ'],
        ],
    ],

    /*
     * الأدوار التي تُنشأ مع النظام (ولا تُحذف). المحرر يطابق صلاحيات
     * «المحرر» في النظام القديم: المحتوى كله، بلا الأعضاء والإعدادات.
     */
    'system_roles' => [
        'admin' => [
            'name' => 'مدير النظام',
            'description' => 'كل الصلاحيات، بما فيها الأعضاء والأدوار والإعدادات.',
            'permissions' => ['*'],
        ],
        'editor' => [
            'name' => 'محرر',
            'description' => 'يدير المحتوى: الأخبار والنشرات والأقسام والصحف والبانرات والملفات.',
            'permissions' => [
                'news.view', 'news.create', 'news.update', 'news.delete', 'news.publish', 'news.order', 'news.manage_all',
                'publications.view', 'publications.create', 'publications.update', 'publications.delete', 'publications.publish',
                'categories.view', 'categories.create', 'categories.update', 'categories.delete', 'categories.publish',
                'newspapers.view', 'newspapers.create', 'newspapers.update', 'newspapers.delete', 'newspapers.publish',
                'banners.view', 'banners.create', 'banners.update', 'banners.delete', 'banners.publish',
                'uploads.view', 'uploads.create', 'uploads.delete',
                'pdf_templates.view',
            ],
        ],
    ],
];
