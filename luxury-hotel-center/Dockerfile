FROM php:8.2-cli

# 1. Cài đặt các công cụ hệ thống (Đã thêm libpng, libjpeg, freetype cho đồ họa Captcha và libzip)
RUN apt-get update -y && apt-get install -y \
    libmcrypt-dev \
    unzip \
    libpq-dev \
    libmariadb-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev

# 2. Bật công cụ vẽ đồ họa GD và các kết nối Database
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip

# 3. Cài Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 4. Copy code vào vùng chạy
WORKDIR /app
COPY . /app

# 5. Cài thư viện Laravel (Bây giờ có công cụ vẽ GD rồi, Captcha sẽ cài mượt mà)
RUN composer install

# 6. Mở cổng và khởi động Server
EXPOSE 80
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=80