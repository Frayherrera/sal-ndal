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

# 3) Limpiar cachés de Laravel
echo "[entrypoint] Limpiando cachés de Laravel..."
php artisan optimize:clear || true

exec docker-php-entrypoint "$@"
