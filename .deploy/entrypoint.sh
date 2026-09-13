#!/bin/sh
set -e

php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\PermissionSeeder --force
php artisan db:seed --class=Database\\Seeders\\RoleSeeder --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec frankenphp run --config /etc/caddy/Caddyfile
