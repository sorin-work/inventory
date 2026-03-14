#!/bin/sh
set -e

cd /app

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generate one with: php artisan key:generate --show" >&2
    exit 1
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force --no-interaction

if [ "$SEED_DEMO" = "true" ]; then
    php artisan db:seed --force --no-interaction
fi

exec "$@"
