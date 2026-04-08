#!/usr/bin/env sh
# Pre-deploy: runs after build, before the new container serves traffic.
set -eu
cd "$(dirname "$0")/.." || exit 1

php artisan config:clear 2>/dev/null || true

php artisan migrate --force
php artisan storage:link 2>/dev/null || true

# Without a persistent volume on storage/, keys are recreated on fresh deploy and existing tokens break.
if [ ! -f storage/oauth-private.key ]; then
	echo "Generating Passport keys (mount a Railway Volume on storage/ to keep keys across deploys)"
	php artisan passport:keys --force || true
fi

php artisan optimize
