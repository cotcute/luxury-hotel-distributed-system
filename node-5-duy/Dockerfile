FROM php:8.2-apache

# Cài các extension PHP cần thiết
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Cài Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set thư mục làm việc
WORKDIR /var/www/html

# Copy toàn bộ code vào container
COPY . .

# Cài dependencies (không cài dev packages)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Tạo .env từ .env.example nếu chưa có
RUN cp -n .env.example .env || true

# Generate app key
RUN php artisan key:generate --force

# Cấp quyền cho thư mục storage và cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Bật mod_rewrite cho Apache (Laravel cần)
RUN a2enmod rewrite

# Copy Apache config
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]
