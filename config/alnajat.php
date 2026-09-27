<?php

return [
    /*
     * نسخ قالب النشرة (PDF) حسب رقم النشرة، مأخوذة حرفياً من دالة pdfVersion()
     * في includes/function.php القديم. تُستخدم مرة واحدة عند الهجرة لملء
     * العمود publications.pdf_version، ثم تصبح النسخة حقلاً يُختار من اللوحة.
     *
     * المفتاح = أعلى رقم نشرة يأخذ هذه النسخة.
     */
    'pdf_versions' => [
        427 => 1,
        541 => 2,
        590 => 3,
        605 => 4,
    ],
    'pdf_latest_version' => 5,

    // عدد صناديق الصفحة الرئيسية وصناديق الـ PDF في الإعدادات القديمة (box_*_1..15).
    'home_boxes' => 15,

    // مسار الموقع القديم (لأمر alnajat:assets).
    // نسبي = من جذر المشروع.
    'legacy_path' => str_starts_with($legacy = (string) env('LEGACY_PATH', '../alnajatinfo'), '/') ? $legacy : base_path($legacy),

    /*
     * مكان الصور والملفات المرفوعة. القيم في قاعدة البيانات تبقى «upload/…» كما في الموقع القديم،
     * وتُحوَّل هنا فقط (App\Support\Media):
     *   root: المجلد على القرص (الافتراضي storage/app/public/upload، ويُنشر بـ php artisan storage:link)
     *   url:  مساره في الموقع (الافتراضي storage/upload ← /storage/upload/…)
     *   legacy_redirect: تحويل الروابط القديمة /upload/… إلى المكان الجديد (301).
     * للرجوع إلى المجلد القديم: UPLOADS_ROOT=public/upload و UPLOADS_URL=upload
     */
    'uploads' => [
        'root' => rtrim(($root = (string) env('UPLOADS_ROOT', '')) === '' ? storage_path('app/public/upload') : (str_starts_with($root, '/') ? $root : base_path($root)), '/'),
        'url' => trim((string) env('UPLOADS_URL', 'storage/upload'), '/'),
        'legacy_redirect' => (bool) env('UPLOADS_LEGACY_REDIRECT', true),
    ],

    // النطاقات التي تُحذف من بداية روابط الصور القديمة لتصبح نسبية.
    'legacy_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('LEGACY_HOSTS', 'alnajat.info,www.alnajat.info'))
    ))),

    // أدوار المستخدمين. user_group = 0 في النظام القديم كان يعني صلاحية كاملة.
    'roles' => [
        'admin' => 'مدير',
        'editor' => 'محرر',
    ],

    /*
     * أنواع الأخبار كما في لوحة التحكم القديمة (news.type).
     * categories: الأقسام المسموحة لكل نوع (categories_id_by_parent القديمة).
     *   إن كان للنوع قسم واحد يُضاف تلقائياً بلا اختيار.
     * fields: الحقول التي يعرضها نموذج هذا النوع، بالترتيب.
     * labels: تسميات تختلف حسب النوع.
     */
    'news_types' => [
        1 => [
            'label' => 'خبر',
            'icon' => 'news',
            'categories' => [12, 10, 9, 7, 6, 1],
            'fields' => ['title', 'image', 'source_url', 'newspaper', 'newspaper_number', 'published_date',
                'description', 'body', 'categories', 'pdf_flags'],
            'labels' => ['source_url' => 'المصدر'],
        ],
        2 => [
            'label' => 'صوتي',
            'icon' => 'sound',
            'categories' => [4],
            'fields' => ['title', 'description', 'sound_url', 'image', 'body', 'published_date', 'categories'],
        ],
        3 => [
            'label' => 'مرئي',
            'icon' => 'video',
            'categories' => [5],
            'fields' => ['title', 'description', 'video_url', 'image', 'body', 'categories', 'published_date'],
        ],
        4 => [
            'label' => 'تغريدة',
            'icon' => 'tweet',
            'categories' => [3],
            'fields' => ['title', 'description', 'tweet_url', 'image', 'categories', 'published_date', 'pdf_flags'],
            'labels' => ['description' => 'نص التغريدة'],
        ],
        5 => [
            'label' => 'مشروع',
            'icon' => 'project',
            'categories' => [11],
            'fields' => ['title', 'description', 'source_url', 'image', 'categories', 'published_date', 'pdf_flags'],
            'labels' => ['source_url' => 'رابط التبرع'],
        ],
    ],

    // مقاسات الصور المصغّرة كما في crop() القديمة: upload/thumbs/{name}_{w}x{h}.{ext}
    // + upload/{name}_thumbnail.{ext} بعرض 200.
    'image_crops' => [
        'xsmall' => [150, 150],
        'small' => [150, 50],
        'medium' => [350, 155],
        'large' => [920, 550],
    ],
    'image_thumbnail_width' => 200,
    'image_max_kb' => 10240,

    /*
     * قوالب عرض الصناديق كما في news_template() و news_template_pdf() القديمتين.
     * الاسم والوصف يظهران في «الإعدادات» مع رسم مصغّر (components/admin/template-thumb).
     */
    'box_templates' => [
        'home' => [
            1 => ['name' => 'خبر رئيسي', 'hint' => 'صورة عريضة ثم العنوان ومقتطف حتى 60 كلمة.'],
            5 => ['name' => 'خبر بوصف كامل', 'hint' => 'مثل الخبر الرئيسي مع الوصف كاملاً وخلفية الأخبار الخيرية.'],
            6 => ['name' => 'عمودان', 'hint' => 'خبران متجاوران، لكل خبر صورة متوسطة وعنوان ومقتطف.'],
            7 => ['name' => 'قائمة', 'hint' => 'صورة صغيرة بجانب العنوان والمقتطف، بلا عنوان للقسم.'],
            8 => ['name' => 'صورة فقط', 'hint' => 'صورة الخبر كاملة بلا نص، مناسبة للإنفوجرافيك.'],
            2 => ['name' => 'تغريدات', 'hint' => 'بطاقتان في كل صف: شعار دائري ونص التغريدة وزر الرابط.'],
            3 => ['name' => 'صوتيات', 'hint' => 'صورة صغيرة والعنوان والوصف مع زر «استمع الآن».'],
            4 => ['name' => 'مرئيات', 'hint' => 'النص بجانب فيديو يوتيوب مضمّن وزر «شاهد الآن».'],
        ],
        'pdf' => [
            1 => ['name' => 'خبر مع شعار الصحيفة', 'hint' => 'شعار المصدر وصورة كبيرة والعنوان والوصف، مع أزرار المصدر والتغريدة والصوت والفيديو.'],
            5 => ['name' => 'خبر بصورة كبيرة', 'hint' => 'صورة كبيرة والعنوان والوصف وزر «أكمل القراءة».'],
            6 => ['name' => 'عمودان', 'hint' => 'خبران متجاوران في الصفحة.'],
            7 => ['name' => 'قائمة', 'hint' => 'صورة جانبية صغيرة مع العنوان والمقتطف.'],
            8 => ['name' => 'صورة مع المصدر', 'hint' => 'الصورة كاملة مع شعار الصحيفة وزر «طالع المصدر».'],
            9 => ['name' => 'مشروع تبرع', 'hint' => 'صورة المشروع ووصفه وزر «تبرع الآن».'],
            2 => ['name' => 'تغريدتان', 'hint' => 'كل تغريدتين في صفحة واحدة.'],
            3 => ['name' => 'صوتيات في صفحة', 'hint' => 'كل أخبار القسم في صفحة واحدة مع «استمع الآن».'],
            4 => ['name' => 'مرئي', 'hint' => 'النص وصورة مصغّرة وزر «شاهد الآن».'],
        ],
    ],

    /*
     * الـ PDF (نفس إعدادات includes/pdf.php القديم).
     * page_classes: صنف صفحة كل قسم حسب رقمه، تستخدمه ملفات css/pdf-vN.css للخلفيات.
     */
    'pdf' => [
        'fonts_path' => resource_path('fonts'),
        'fonts' => [
            'tajawal' => ['R' => 'tajawal-regular.ttf', 'B' => 'tajawal-bold.ttf'],
            'frutiger' => ['R' => 'frutiger-lt-arabic-55-roman.ttf', 'B' => 'frutiger-lt-arabic-65-bold.ttf'],
            'awanzamanth' => ['R' => 'awanzamanth.ttf'],
            'stc' => ['R' => 'stc-regular.ttf'],
            'swissracondensed' => ['R' => 'swissracondensed-normal.ttf', 'B' => 'swissracondensed-bold.ttf'],
            'al-jazeera-arabic-regular' => ['R' => 'al-jazeera-arabic-regular.ttf'],
            'bahij-insan' => ['R' => 'bahij-insan.ttf'],
            'ae-almateen-bold' => ['R' => 'ae-almateen-bold.ttf'],
            'harf-fannan' => ['R' => 'harf-fannan.ttf'],
            'cairo' => ['R' => 'cairo-regular.ttf', 'B' => 'cairo-bold.ttf'],
            'dubai' => ['R' => 'dubai-regular.ttf', 'B' => 'dubai-bold.ttf'],
        ],
        'page_classes' => [
            1 => 'magazine_page', 2 => 'magazine_page', 3 => 'social_page', 4 => 'radio_page',
            5 => 'tv_page', 6 => 'work_page', 7 => 'work_in_kuwait_page', 8 => 'work_in_gulf_page',
            9 => 'article_page', 10 => 'idea_page', 11 => 'project_page', 12 => 'volunteer_page',
        ],
        // القسم الصوتي: عرضه الخاص في صفحة واحدة (pdf_template القديمة).
        'radio_category' => 4,
        // دقائق قبل أن يُعاد توليد PDF «أخبار اليوم» ولو لم يتغير شيء.
        'today_ttl' => 15,
    ],
];
