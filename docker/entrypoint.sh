#!/bin/sh
set -e

echo "Starting ChartSense AI Production Boot Sequence..."

# Set APP_URL from Render or Railway if present
if [ -n "$RENDER_EXTERNAL_URL" ] && [ -z "$APP_URL" ]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
elif [ -n "$RAILWAY_PUBLIC_DOMAIN" ] && [ -z "$APP_URL" ]; then
    export APP_URL="https://$RAILWAY_PUBLIC_DOMAIN"
fi

# Ensure storage permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage link if missing
php artisan storage:link --force || true

# Wait for database if DB_HOST is set
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database ($DB_HOST:${DB_PORT:-5432}) to become ready..."
    for i in $(seq 1 30); do
        nc -z "$DB_HOST" "${DB_PORT:-5432}" && break || true
        sleep 1
    done
fi

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
