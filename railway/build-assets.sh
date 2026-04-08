#!/usr/bin/env sh
# Railpack may install node first; npm ci then hits EBUSY on node_modules/.cache (overlayfs).
# Use a cache outside node_modules and a clean install + mix production build.
set -eu
export npm_config_cache="/tmp/npm-cache"
rm -rf node_modules
npm install --legacy-peer-deps --no-audit
npm run production
