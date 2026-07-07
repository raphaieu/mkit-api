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

if [ -z "$APP_KEY" ]; then
    echo "ERRO: APP_KEY não configurada."
    echo "Defina APP_KEY nas variáveis de ambiente do Coolify."
    echo "Gere uma chave com: php artisan key:generate --show"
    exit 1
fi

wait_for_service "${DB_HOST:-mysql}" "${DB_PORT:-3306}"
wait_for_service "${REDIS_HOST:-redis}" "${REDIS_PORT:-6379}"

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache public

run_artisan() {
    gosu www-data:www-data php artisan "$@"
}

if [ "$APP_ROLE" = "web" ] || [ -z "$APP_ROLE" ]; then
    run_artisan package:discover --ansi
    run_artisan storage:link --force 2>/dev/null || true

    if ! run_artisan db:monitor --databases=mysql --max=1 2>/dev/null; then
        echo "AVISO: não foi possível conectar ao MySQL. Verifique DB_* e credenciais."
    fi

    if [ "$RUN_MIGRATIONS" = "true" ]; then
        run_artisan migrate --force
    fi

    run_artisan config:cache
    run_artisan route:cache
    run_artisan view:cache
fi

if [ "$1" = "php-fpm" ]; then
    exec php-fpm
fi

exec gosu www-data:www-data "$@"
