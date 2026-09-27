# نشر موقع النجاة على VPS

الافتراض: Ubuntu 24.04، Nginx، PHP 8.4-FPM، MySQL 8، Redis، والموقع القديم ما زال يعمل على نفس الخادم أو خادم آخر حتى يوم الانتقال.

## 1) الحزم

```bash
sudo apt install nginx mysql-server redis-server supervisor unzip git \
  php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath php8.4-redis
# Composer: https://getcomposer.org/download/
```

`php.ini` (fpm وcli):

```ini
memory_limit = 512M          ; mPDF للنشرات الكبيرة
upload_max_filesize = 20M
post_max_size = 64M          ; رفع عدة ملفات دفعة واحدة
max_execution_time = 120
opcache.enable = 1
opcache.validate_timestamps = 0   ; بعد كل نشر: php artisan optimize && sudo systemctl reload php8.4-fpm
```

## 2) الكود

```bash
sudo mkdir -p /var/www/alnajat && sudo chown $USER:www-data /var/www/alnajat
cd /var/www/alnajat
git clone <repo> current && cd current
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
```

`.env` للإنتاج:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.alnajat.info
APP_LOCALE=ar
APP_TIMEZONE=Asia/Kuwait

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=alnajat
DB_USERNAME=alnajat
DB_PASSWORD=...

LEGACY_DB_DATABASE=info          # قراءة فقط، لـ legacy:import / verify / export-since
LEGACY_DB_USERNAME=alnajat_ro    # مستخدم بصلاحية SELECT فقط
LEGACY_DB_PASSWORD=...
LEGACY_PATH=/var/www/alnajatinfo # الموقع القديم: css، images، upload، الخطوط

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_CLIENT=phpredis
```

الصلاحيات:

```bash
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
php artisan storage:link   # public/storage ← storage/app/public (الصور المرفوعة في storage/app/public/upload)
```

## 3) الملفات القديمة

```bash
# نسخ (لا رابط رمزي) حتى لا يعتمد الموقع الجديد على مجلد القديم بعد الانتقال
php artisan alnajat:assets --copy
```

ينسخ `css` و`js` و`images` إلى `public/`، والخطوط إلى `resources/fonts`، والصور `upload` إلى
`storage/app/public/upload` عبر `alnajat:move-uploads` (يستبعد ملفات PHP و`.htaccess`، ويكمل الناقص
عند إعادته، ويتحقق من مسارات القاعدة). تُعرض الصور من `/storage/upload/…`، والقيم في القاعدة تبقى `upload/…`.

لنقل مجلد موجود أصلاً في `public/upload` (مثل بيئة سابقة):

```bash
php artisan alnajat:move-uploads --dry-run   # العدد والحجم والمساحة الحرة والتعارضات، بلا نسخ
php artisan alnajat:move-uploads             # نسخ (المصدر يبقى)، أو --move لنقل وحذف من المصدر
rm -r public/upload                          # بعد التأكد فقط: تعمل بعدها تحويلات /upload/… ← /storage/upload/…
```

للرجوع إلى المجلد القديم بلا تعديل كود: `UPLOADS_ROOT=public/upload` و`UPLOADS_URL=upload` في `.env` ثم `php artisan config:cache`.

## 4) Nginx

`/etc/nginx/sites-available/alnajat`:

```nginx
server {
    listen 80;
    server_name alnajat.info www.alnajat.info;
    return 301 https://www.alnajat.info$request_uri;
}

server {
    listen 443 ssl http2;
    server_name alnajat.info;
    ssl_certificate     /etc/letsencrypt/live/alnajat.info/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/alnajat.info/privkey.pem;
    return 301 https://www.alnajat.info$request_uri;
}

server {
    listen 443 ssl http2;
    server_name www.alnajat.info;
    root /var/www/alnajat/current/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/alnajat.info/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/alnajat.info/privkey.pem;

    client_max_body_size 64M;
    charset utf-8;

    # الصور والملفات الثابتة
    location ~* \.(?:css|js|jpe?g|png|gif|webp|svg|ico|woff2?|ttf|pdf)$ {
        expires 30d;
        access_log off;
        try_files $uri =404;
    }

    # الصور المرفوعة (storage/app/public عبر الرابط public/storage): لا تنفيذ PHP أبداً
    location ^~ /storage/ {
        location ~* \.(php\d?|phtml|phar)$ { deny all; }
        expires 30d;
        access_log off;
        try_files $uri =404;
    }

    # الروابط القديمة للصور ← المكان الجديد (مواقع نقلت عنا، ونصوص الأخبار، والـ PDF القديمة)
    location ^~ /upload/ {
        rewrite ^/upload/(.*)$ /storage/upload/$1 permanent;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /index.php {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;   # أول توليد لنشرة كبيرة
    }

    location ~ \.php$ { return 404; }
    location ~ /\.(?!well-known) { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/alnajat /etc/nginx/sites-enabled/
sudo certbot --nginx -d alnajat.info -d www.alnajat.info
sudo nginx -t && sudo systemctl reload nginx
```

روابط الموقع القديم (`show/…`، `pdf-show/…`، `category/…`، `search/…`، `publication/…`، `archive.html`، `today-news.html`، و`index.php?action=…`) كلها تعمل من Laravel؛ لا حاجة لقواعد rewrite في Nginx.

## 5) الطابور والجدولة

`/etc/supervisor/conf.d/alnajat-worker.conf`:

```ini
[program:alnajat-worker]
command=php /var/www/alnajat/current/artisan queue:work redis --sleep=3 --tries=2 --timeout=900 --max-time=3600
user=www-data
numprocs=1
autostart=true
autorestart=true
stopwaitsecs=900
redirect_stderr=true
stdout_logfile=/var/www/alnajat/current/storage/logs/worker.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
```

Cron لـ `www-data` (`sudo crontab -u www-data -e`):

```cron
* * * * * cd /var/www/alnajat/current && php artisan schedule:run >> /dev/null 2>&1
```

المهام المجدولة (`routes/console.php`):

- كل 5 دقائق `pdf:warm --limit=1`: نشرة اليوم جاهزة دائماً، ولا يُعاد توليدها إلا إن تغيّر شيء أو مضت `today_ttl` (15 دقيقة).
- يومياً: تنظيف ملفات mPDF المؤقتة.

## 6) النسخ الاحتياطي

```bash
# /etc/cron.d/alnajat-backup
30 3 * * * root mysqldump --single-transaction alnajat | gzip > /var/backups/alnajat/db-$(date +\%F).sql.gz && find /var/backups/alnajat -name 'db-*.sql.gz' -mtime +30 -delete
45 3 * * 0 root tar czf /var/backups/alnajat/upload-$(date +\%F).tgz -C /var/www/alnajat/current/storage/app/public upload
```

ملفات `storage/app/pdf` لا تحتاج نسخاً: تُولَّد عند الطلب.

## 7) بيئة التجربة (قبل الانتقال بأسبوع)

نفس الخطوات 1–6 على نطاق فرعي (مثل `staging.alnajat.info`) بقاعدة `alnajat_staging`، ثم:

```bash
php artisan legacy:import --fresh --force && php artisan legacy:verify
php artisan alnajat:check-links https://staging.alnajat.info --sample=200   # كل الروابط 200 أو 301
php artisan alnajat:check-links https://staging.alnajat.info --sample=40 --pdf
php artisan pdf:samples --old=https://www.alnajat.info                        # نشرة من كل نسخة v1..v5 من النظامين
```

- `check-links` يأخذ معرّفات حقيقية من القاعدة القديمة، يجرّب الشكلين (`show/15` و`index.php?action=show&id=15`)، ويكتب تقريراً CSV في `storage/app/private`. لا يُحتسب زيارة.
- `pdf:samples` يضع كل زوج (`v3-512-new.pdf` و`v3-512-old.pdf`) في `storage/app/private/pdf-samples` للمقارنة بالعين.
- المحررون يدخلون بحساباتهم الحقيقية ويضيفون أخباراً تجريبية.

## 8) يوم الانتقال

1. إعلان توقف قصير للمحررين عن الإضافة في اللوحة القديمة.
2. الموقع الجديد متاح على عنوان مؤقت (مثل `new.alnajat.info`) بنفس إعدادات Nginx.
3. تشغيل السكربت، وهو يتوقف عند أول خطوة تفشل:

```bash
LEGACY_DB=info bash deploy/cutover.sh https://new.alnajat.info
```

يسجّل وقت الانتقال في `storage/app/private/cutover-at.txt`، ويأخذ نسخة من القاعدة القديمة، ثم `migrate` و`legacy:import` و`legacy:verify` و`alnajat:assets --copy` و`pdf:warm --limit=50` و`alnajat:check-links`.

4. تحويل Nginx للنطاق الأساسي، ثم `php artisan alnajat:check-links https://www.alnajat.info --sample=100`.
5. الدخول بحساب قديم، إضافة خبر بصورة، فتح `today-news.html`.

**فترة المراقبة (أسبوعان):** صفحة «روابط مفقودة» في اللوحة (`/cp/missing-links`، للمدير) تجمع كل رابط أعاد 404 مع عدد مرات طلبه والصفحة التي أحالت إليه، مرتبة بالأكثر طلباً. وسجل الأخطاء في `storage/logs/laravel.log`.

## 9) التراجع

إن احتجنا العودة للموقع القديم بعد أيام من الانتقال بلا فقد ما أُضيف:

```bash
php artisan legacy:export-since "2026-10-01 06:00" --deletes
# → storage/app/private/legacy/rollback-….sql
mysqldump info > info-before-rollback.sql     # احتياطاً
mysql info < storage/app/private/legacy/rollback-….sql
```

يصدّر الأقسام والصحف والإعلانات والأخبار (مع أقسامها) والنشرات التي أُضيفت أو عُدّلت منذ وقت الانتقال بصيغة الجداول القديمة (`REPLACE INTO`)، ومع `--deletes` يحذف من القديم ما حُذف في الجديد. الأعضاء الجدد لا يُصدَّرون (كلمات المرور bcrypt)، والأمر ينبّه لذلك.

## 10) كل نشر لاحق

```bash
cd /var/www/alnajat/current && bash deploy/deploy.sh
```

يسحب الكود، ويثبت الحزم، ويشغّل `migrate` و`optimize` في وضع صيانة لثوانٍ، ثم يعيد تحميل PHP-FPM والطابور.
