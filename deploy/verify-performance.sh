#!/usr/bin/env bash
# Production / staging verification for performance fixes (Section D).
# Run on the app host after deploying code.
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Migrations"
php artisan migrate --force

echo "==> Deploy optimize"
bash deploy/optimize.sh

echo "==> Performance diagnose"
php artisan performance:diagnose

echo ""
echo "==> Manual checks still required:"
echo "  1. Browser Network: time warm TTFB for /dashboard, a class list, and settings"
echo "  2. Confirm class list per_page <= 200"
echo "  3. MySQL slow_query_log (>200ms) near-zero under staff load"
echo "  4. htop: CPU moderate and PHP/MySQL not fighting RAM after Redis"
echo "  5. Scale gate: stay on 8GB unless CPU/RAM saturated after Redis + query fixes"
echo ""
echo "Done."
