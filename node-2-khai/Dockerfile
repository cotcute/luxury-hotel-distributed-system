# 1. Dùng môi trường PHP 8.2 (Bản CLI nhẹ nhàng chuyên để chạy lệnh)
FROM php:8.2-cli

# 2. Thiết lập thư mục làm việc mặc định
WORKDIR /app

# 3. Cài đặt các thư viện lõi của Linux (Đã thêm lệnh dọn dẹp để file nhẹ hơn)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 4. Cài đặt các tiện ích mở rộng của PHP cho Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# 5. Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Copy toàn bộ mã nguồn vào Docker
COPY . /app

# 7. CHỐNG LỖI 500: Cấp quyền ghi cho 2 thư mục này (Render rất hay bắt bẻ lỗi này)
RUN chmod -R 777 storage bootstrap/cache

# 8. Cài đặt thư viện Laravel (Đã chặn cảnh báo + Tối ưu hóa tốc độ load web)
RUN composer config audit.block-insecure false && composer install --optimize-autoloader --no-interaction

# 9. Lệnh khởi động Server trên Render (Tự động nhận diện Cổng)
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}