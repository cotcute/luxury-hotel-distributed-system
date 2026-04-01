FROM php:8.2-cli

# Cài đặt các thư viện cần thiết cho Laravel và MySQL (Aiven)
RUN apt-get update -y && apt-get install -y libmcrypt-dev unzip libpq-dev libmariadb-dev
RUN docker-php-ext-install pdo pdo_mysql

# Cài đặt Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY . /app

# Chạy cài đặt vendor
RUN composer install

# Mở cổng 80 và chạy server
EXPOSE 80
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=80