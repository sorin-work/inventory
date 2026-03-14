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

# The database container reports healthy before it accepts every
# connection, so give migrations a few tries.
tries=0
until php artisan migrate --force --no-interaction; do
    tries=$((tries + 1))
    if [ "$tries" -ge 10 ]; then
        echo "Migrations failed after $tries attempts." >&2
        exit 1
    fi
    echo "Database not ready, retrying in 3s..." >&2
    sleep 3
done

if [ "$SEED_DEMO" = "true" ]; then
    php artisan db:seed --force --no-interaction
fi

exec "$@"
