FROM php:8.3-cli

# Dépendances système + extensions PHP nécessaires à Laravel
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libpng-dev libonig-dev libxml2-dev libpq-dev curl \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql mbstring zip exif pcntl gd

# Installer Node.js (pour npm run build) et Composer
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

EXPOSE 10000
CMD php artisan config:cache && php artisan migrate --force && php artisan serve --host 0.0.0.0 --port $PORT
