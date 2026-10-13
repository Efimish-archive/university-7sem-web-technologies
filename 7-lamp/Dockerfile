FROM docker.io/php:8.3-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libxml2-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=docker.io/composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress

RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads
RUN printf 'upload_max_filesize=5M\npost_max_size=7M\n' > /usr/local/etc/php/conf.d/uploads.ini
