# Menggunakan sistem operasi Linux dasar yang sudah terpasang PHP dan Web Server Apache
FROM php:8.4-apache

# Menginstal ekstensi sistem yang dibutuhkan oleh Laravel, Filament, dan PostgreSQL (Supabase)
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql intl zip bcmath gd

# Mengaktifkan modul URL Rewrite bawaan Apache agar routing Laravel berfungsi
RUN a2enmod rewrite

# Mengubah arah folder utama web server ke folder /public milik Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Memasang Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Mengatur folder kerja di dalam server
WORKDIR /var/www/html

# Menyalin seluruh file proyek Anda dari komputer ke dalam server
COPY . .

# Menginstal library/vendor Laravel
RUN composer install --optimize-autoloader --no-dev

# Memberikan hak akses/izin agar Laravel bisa menulis file (seperti cache dan log)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Membuka port 80 untuk akses internet
EXPOSE 80

# Perintah yang dijalankan otomatis saat server menyala (Migrasi Database & Menyalakan Web Server)
CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan filament:cache-components && \
    php artisan migrate --force && \
    apache2-foreground
