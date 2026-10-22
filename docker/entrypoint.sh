#!/bin/sh
set -e

echo "Starting ChartSense AI Production Boot Sequence..."

# Ensure storage permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage link if missing
php artisan storage:link --force || true

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Optimize Laravel caching
echo "Caching Laravel configuration and routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM in background
echo "Starting PHP-FPM..."
php-fpm -D

# Start Nginx in foreground
echo "ChartSense AI is live! Starting Nginx..."
nginx -g "daemon off;"
