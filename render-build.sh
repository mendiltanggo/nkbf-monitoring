#!/usr/bin/env bash
# Hentikan proses jika ada error
set -o errexit

# Install dependencies tanpa package developer
composer install --optimize-autoloader --no-dev

# Bersihkan dan buat ulang cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components

# Jalankan migrasi database otomatis ke Supabase
php artisan migrate --force