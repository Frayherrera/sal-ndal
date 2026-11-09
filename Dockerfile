FROM php:8.2-apache

# Instalar dependencias del sistema y extensiones PHP
RUN apt-get update && apt-get install -y \
    git unzip curl libpq-dev libzip-dev zip \
    && docker-php-ext-install pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Activar mod_rewrite
RUN a2enmod rewrite

# Copiar composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar solo los manifiestos primero para aprovechar la cache de capas
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --optimize-autoloader

# Copiar el resto del proyecto
COPY . .

# Descubrir paquetes (se omitió durante el install por falta del código)
RUN php artisan package:discover --ansi || true

# Permisos de escritura para Laravel
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Limpiar cachés (config/rutas/vistas) para que el contenedor arranque limpio.
# Se fuerzan drivers de archivo porque el .env no entra en el contexto de build.
RUN CACHE_STORE=file SESSION_DRIVER=file QUEUE_CONNECTION=sync php artisan optimize:clear || true

# Script de arranque: instala dependencias, ajusta permisos y limpia cachés
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Config Apache
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
