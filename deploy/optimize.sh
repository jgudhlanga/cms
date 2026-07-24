#!/usr/bin/env bash
# Production deploy optimize step — run after code sync & composer install --no-dev.
set -euo pipefail

cd "$(dirname "$0")/.."

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan performance:diagnose || true

echo "Deploy optimize complete."
