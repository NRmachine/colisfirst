#!/bin/sh
set -eu
cd /var/www/html

: "${APP_KEY:?APP_KEY must be supplied as a private environment variable}"
: "${APP_URL:?APP_URL must be supplied}"
case "$APP_URL" in https://*) ;; *) echo 'APP_URL must use HTTPS.' >&2; exit 1 ;; esac
case "${PORT:-8080}" in ''|*[!0-9]*) echo 'Invalid PORT.' >&2; exit 1 ;; esac
if [ "${APP_ENV}" != production ] || [ "${APP_DEBUG}" != false ] || [ "${SESSION_SECURE_COOKIE}" != true ]; then
    echo 'Production requires APP_ENV=production, APP_DEBUG=false and SESSION_SECURE_COOKIE=true.' >&2
    exit 1
fi
if [ "${STRIPE_LIVE}" != false ] || [ "${MARKETPLACE_DEMO}" != false ]; then
    echo 'This deployment keeps Stripe in test mode and disables demo payments.' >&2
    exit 1
fi

mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs storage/database bootstrap/cache
if [ "${DB_CONNECTION:-sqlite}" = sqlite ]; then
    : "${DB_DATABASE:?Use /var/www/html/storage/database/database.sqlite on the persistent volume}"
    case "$DB_DATABASE" in /var/www/html/storage/database/*.sqlite) ;; *) echo 'SQLite must be stored on the persistent storage volume.' >&2; exit 1 ;; esac
    touch "$DB_DATABASE"
fi
chown -R www-data:www-data storage bootstrap/cache
chmod -R u+rwX,g+rwX storage bootstrap/cache
sed "s/__PORT__/${PORT:-8080}/g" /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf
php artisan config:cache
php artisan migrate --force --no-interaction
php artisan storage:link --force
php artisan route:cache
php artisan view:cache
exec "$@"
