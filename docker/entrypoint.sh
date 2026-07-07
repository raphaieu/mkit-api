#!/bin/sh
set -e

wait_for_service() {
    host="$1"
    port="$2"

    if [ -z "$host" ] || [ -z "$port" ]; then
        return 0
    fi

    echo "Aguardando ${host}:${port}..."

    while ! nc -z "$host" "$port" 2>/dev/null; do
        sleep 2
    done
}

wait_for_service "${DB_HOST:-mysql}" "${DB_PORT:-3306}"
wait_for_service "${REDIS_HOST:-redis}" "${REDIS_PORT:-6379}"

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public

if [ "$APP_ROLE" = "web" ] || [ -z "$APP_ROLE" ]; then
    php artisan package:discover --ansi
    php artisan storage:link 2>/dev/null || true

    if [ "$RUN_MIGRATIONS" = "true" ]; then
        php artisan migrate --force
    fi

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
