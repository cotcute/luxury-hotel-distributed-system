FROM php:8.2-cli

RUN apt-get update -y && apt-get install -y libmcrypt-dev unzip libpq-dev libmariadb-dev libpng-dev libjpeg-dev libfreetype6-dev libzip-dev
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install gd pdo pdo_mysql zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY . /app

RUN composer install --no-dev --optimize-autoloader

# --- ĐÂY LÀ ĐOẠN MA THUẬT QUYẾT ĐỊNH ---
# Chúng ta sẽ viết 1 script nhỏ để tự động tạo file .env TỪ CHÍNH NHỮNG GÌ BẠN NHẬP TRÊN RENDER
RUN echo '#!/bin/sh' > start.sh
RUN echo 'echo "APP_NAME=Laravel" > .env' >> start.sh
RUN echo 'echo "APP_ENV=${APP_ENV}" >> .env' >> start.sh
RUN echo 'echo "APP_KEY=${APP_KEY}" >> .env' >> start.sh
RUN echo 'echo "APP_DEBUG=${APP_DEBUG}" >> .env' >> start.sh
RUN echo 'echo "DB_CONNECTION=${DB_CONNECTION}" >> .env' >> start.sh
RUN echo 'echo "DB_HOST=${DB_HOST}" >> .env' >> start.sh
RUN echo 'echo "DB_PORT=${DB_PORT}" >> .env' >> start.sh
RUN echo 'echo "DB_DATABASE=${DB_DATABASE}" >> .env' >> start.sh
RUN echo 'echo "DB_USERNAME=${DB_USERNAME}" >> .env' >> start.sh
RUN echo 'echo "DB_PASSWORD=${DB_PASSWORD}" >> .env' >> start.sh

RUN echo 'php artisan serve --host=0.0.0.0 --port=80' >> start.sh
RUN chmod +x start.sh
# --------------------------------------

EXPOSE 80

# Chạy script ma thuật thay vì chạy thẳng lệnh
CMD ["/app/start.sh"]