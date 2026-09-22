#!/bin/bash
# Clear cached config (cached during build without env vars)
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Re-cache with actual environment variables
php artisan config:cache
php artisan route:cache

# Run migrations
php artisan migrate --force

# Start the server
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
