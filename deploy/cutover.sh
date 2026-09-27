#!/usr/bin/env bash
# يوم الانتقال: ينقل البيانات ويتحقق منها ويتوقف عند أول مشكلة.
# لا يغيّر Nginx: تحويل النطاق خطوة يدوية أخيرة بعد نجاح هذا السكربت.
#
# قبل التشغيل: أوقف الإضافة في اللوحة القديمة، واجعل الموقع الجديد متاحاً على
# عنوان مؤقت (مثل new.alnajat.info) ليُفحص قبل تحويل النطاق الأساسي.
# mysqldump يأخذ بيانات الدخول من ~/.my.cnf.
#
#   LEGACY_DB=info bash deploy/cutover.sh https://new.alnajat.info
set -euo pipefail
cd "$(dirname "$0")/.."

SITE="${1:?اكتب العنوان المؤقت للموقع الجديد، مثل https://new.alnajat.info}"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/alnajat}"
LEGACY_DB="${LEGACY_DB:-info}"

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }

step "0) وقت الانتقال (احفظه: يلزم لأمر التراجع)"
CUTOVER_AT="$(date '+%Y-%m-%d %H:%M:%S')"
mkdir -p storage/app/private && echo "$CUTOVER_AT" | tee "storage/app/private/cutover-at.txt"

step "1) نسخة احتياطية من القاعدة القديمة ($LEGACY_DB)"
mkdir -p "$BACKUP_DIR"
mysqldump --single-transaction "$LEGACY_DB" | gzip > "$BACKUP_DIR/legacy-$STAMP.sql.gz"
ls -lh "$BACKUP_DIR/legacy-$STAMP.sql.gz"

step "2) الجداول"
php artisan migrate --force

step "3) نقل البيانات"
php artisan legacy:import --fresh --force

step "4) التحقق (يتوقف السكربت إن وُجد أي فرق)"
php artisan legacy:verify

step "5) آخر الصور المرفوعة في الموقع القديم"
php artisan alnajat:assets --copy

step "6) التهيئة وتجهيز آخر النشرات"
php artisan optimize
php artisan pdf:warm --limit=50

step "7) فحص الروابط على العنوان المؤقت"
php artisan alnajat:check-links "$SITE" --sample=200

cat <<MSG

تم. الخطوات اليدوية الباقية:
  1. حوّل Nginx للنطاق الأساسي إلى المشروع الجديد:  sudo nginx -t && sudo systemctl reload nginx
  2. php artisan alnajat:check-links https://www.alnajat.info --sample=100
  3. ادخل /cp بحساب قديم، وافتح https://www.alnajat.info/today-news.html
  4. للتراجع لاحقاً:  php artisan legacy:export-since "$CUTOVER_AT" --deletes
MSG
