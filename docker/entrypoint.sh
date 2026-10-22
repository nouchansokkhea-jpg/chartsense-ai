#!/bin/sh
set -e

echo "Starting ChartSense AI Production Boot Sequence..."

# Set APP_URL from Render or Railway if present
if [ -n "$RENDER_EXTERNAL_URL" ] && [ -z "$APP_URL" ]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
elif [ -n "$RAILWAY_PUBLIC_DOMAIN" ] && [ -z "$APP_URL" ]; then
    export APP_URL="https://$RAILWAY_PUBLIC_DOMAIN"
fi

# Dynamically listen on custom PORT if provided by cloud host
if [ -n "$PORT" ] && [ "$PORT" != "80" ] && [ "$PORT" != "10000" ]; then
    sed -i "s/listen 10000;/listen 10000;\n    listen ${PORT};\n    listen [::]:${PORT};/g" /etc/nginx/http.d/default.conf
fi

# Ensure storage permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage link if missing
php artisan storage:link --force || true

# Generate APP_KEY if missing
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force || true
fi

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
php artisan migrate --force || true

# Optimize Laravel caching
echo "Optimizing Laravel configuration and routes..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Start PHP-FPM in background
echo "Starting PHP-FPM..."
php-fpm -D

# Start Nginx in foreground
echo "ChartSense AI is live on ports 80 & 10000 (and ${PORT:-10000})! Starting Nginx..."
nginx -g "daemon off;"
