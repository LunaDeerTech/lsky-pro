#!/bin/sh
set -eu

WEB_PORT="${WEB_PORT:-8089}"
HTTPS_PORT="${HTTPS_PORT:-8088}"

export WEB_PORT HTTPS_PORT

envsubst '${WEB_PORT} ${HTTPS_PORT}' < /000-default.conf.template > /etc/apache2/sites-enabled/000-default.conf
envsubst '${WEB_PORT} ${HTTPS_PORT}' < /ports.conf.template > /etc/apache2/ports.conf

if [ ! -f /var/www/html/public/index.php ]; then
    cp -r /var/www/lsky/* /var/www/html/
    cp /var/www/lsky/.env.example /var/www/html/
fi

mkdir -p \
    /var/www/html/bootstrap/cache \
    /var/www/html/storage/app \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs

chown -R www-data /var/www/html
chgrp -R www-data /var/www/html
chmod -R 755 /var/www/html/

exec "$@"