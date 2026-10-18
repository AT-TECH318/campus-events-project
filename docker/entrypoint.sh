#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is missing. Set it in the host environment. Do not commit it." >&2
    exit 1
fi

port="${PORT:-80}"
printf 'Listen %s\n' "$port" > /etc/apache2/ports.conf
sed "s/PORT_PLACEHOLDER/${port}/g" /etc/apache2/sites-available/000-default.conf > /tmp/campus-vhost.conf
cp /tmp/campus-vhost.conf /etc/apache2/sites-available/000-default.conf

attempt=1
until su --preserve-environment -s /bin/sh www-data -c "php artisan migrate --force"; do
    if [ "$attempt" -ge 10 ]; then
        echo "Migrations did not complete." >&2
        exit 1
    fi
    attempt=$((attempt + 1))
    sleep 3
done

su --preserve-environment -s /bin/sh www-data -c "php artisan db:seed --class=RoleSeeder --force"
su --preserve-environment -s /bin/sh www-data -c "php artisan db:seed --class=CategorySeeder --force"
su --preserve-environment -s /bin/sh www-data -c "php artisan campus:ensure-initial-admin"

exec apache2-foreground
