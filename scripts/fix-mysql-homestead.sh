#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "=== Fixes homestead MySQL user + password (secret) and imports db_rentasuit_php.sql ==="
echo "Enter MySQL root password when prompted (press Enter if root has no password)."
echo ""

mysql -u root -p < "$ROOT/database/setup/homestead-local.sql"

echo ""
echo "Importing db_rentasuit_php.sql (may take several minutes)..."
mysql -u homestead -psecret homestead < "$ROOT/db_rentasuit_php.sql"

echo ""
echo "Done. From project root run:"
echo "  php artisan config:clear && php artisan serve"
