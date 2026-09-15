#!/usr/bin/env bash
# Production deploy optimize step — run after code sync & composer install --no-dev.
#
# Recommended PHP-FPM OPcache settings for production:
#   opcache.enable=1
#   opcache.memory_consumption=256
#   opcache.max_accelerated_files=20000
#   opcache.validate_timestamps=0   (needs the PHP-FPM reload below on every deploy)
set -euo pipefail

cd "$(dirname "$0")/.."

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# With opcache.validate_timestamps=0, PHP-FPM keeps serving the previous code until it reloads.
# Set PHP_FPM_SERVICE on the server (for example php8.4-fpm) to reload it here.
if [[ -n "${PHP_FPM_SERVICE:-}" ]]; then
    sudo -n systemctl reload "$PHP_FPM_SERVICE"
fi

php artisan performance:diagnose || true

echo "Deploy optimize complete."
