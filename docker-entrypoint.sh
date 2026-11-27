#!/bin/sh
set -e

cd /var/www/html

# 1) Instalar dependencias de Composer si aún no existen.
#    Necesario porque docker-compose monta el proyecto como volumen
#    y oculta el vendor construido en la imagen.
if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] Instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# 2) Permisos de escritura para Laravel
echo "[entrypoint] Ajustando permisos de storage y bootstrap/cache..."
mkdir -p storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 3) Cachés de Laravel
#    En producción se cachean config, rutas, vistas y eventos (menos trabajo por petición).
#    En desarrollo se limpian para ver los cambios al instante.
ENTORNO="${APP_ENV:-$(grep -E '^APP_ENV=' .env 2>/dev/null | cut -d= -f2 | tr -d '\r"')}"
php artisan optimize:clear || true
if [ "$ENTORNO" = "production" ]; then
    echo "[entrypoint] Producción: cacheando config, rutas, vistas y eventos..."
    php artisan optimize || echo "[entrypoint] Aviso: no se pudo cachear; sigue sin caché"
fi

exec docker-php-entrypoint "$@"
