#!/usr/bin/env bash
# نشر تحديث على الخادم (بعد الانتقال). يُشغَّل من جذر المشروع:  bash deploy/deploy.sh
set -euo pipefail
cd "$(dirname "$0")/.."

PHP_FPM="${PHP_FPM:-php8.4-fpm}"

echo "==> سحب الكود"
git pull --ff-only

echo "==> الحزم"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> وضع الصيانة (المحررون يرون صفحة مؤقتة لثوانٍ)"
php artisan down --retry=15 || true
trap 'php artisan up' EXIT

php artisan migrate --force
php artisan optimize

echo "==> إعادة تحميل PHP والطابور"
sudo systemctl reload "$PHP_FPM"
php artisan queue:restart

echo "==> تم"
