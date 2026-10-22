#!/bin/sh
set -e

echo "Starting ChartSense AI Production Boot Sequence..."

# Set APP_URL from Render or Railway if present
if [ -n "$RENDER_EXTERNAL_URL" ] && [ -z "$APP_URL" ]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
elif [ -n "$RAILWAY_PUBLIC_DOMAIN" ] && [ -z "$APP_URL" ]; then
    export APP_URL="https://$RAILWAY_PUBLIC_DOMAIN"
fi

# Map DATABASE_URL to DB_URL if provided
if [ -n "$DATABASE_URL" ] && [ -z "$DB_URL" ]; then
    export DB_URL="$DATABASE_URL"
fi

# Ensure valid base64 APP_KEY for Laravel encryption
case "$APP_KEY" in
    base64:*) ;;
    *) export APP_KEY="base64:eED2lKrWVM85Kp7QblSGLA34XNsrWE6XrCXyGzQG1J0=" ;;
esac

# Dynamically listen on custom PORT if provided by cloud host
if [ -n "$PORT" ] && [ "$PORT" != "80" ] && [ "$PORT" != "10000" ]; then
    sed -i "s/listen 10000;/listen 10000;\n    listen ${PORT};\n    listen [::]:${PORT};/g" /etc/nginx/http.d/default.conf
fi

# Ensure storage directories exist and have proper permissions
mkdir -p /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

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

# Run database migrations and seed administrative accounts
echo "Running database migrations and seeders..."
php artisan migrate --force || true
php artisan db:seed --force || true

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
