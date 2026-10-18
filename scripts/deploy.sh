#!/usr/bin/env bash
# Deploys one commit into the current directory (the app checkout on the server).
# Called by .github/workflows/deploy.yml:  bash scripts/deploy.sh <commit-sha>
# Can also be run by hand on the server:   bash scripts/deploy.sh origin/main
set -euo pipefail

REF="${1:?usage: deploy.sh <commit-sha|ref>}"

echo "==> Deploying $REF into $(pwd)"

php artisan down --retry=60 --refresh=15 || true
# Always bring the site back up, even when a step below fails.
trap 'php artisan up' EXIT

# The server checkout is deploy-only: discard any local edits to tracked files.
# Untracked files (.env, public/uploads, storage) are left alone.
git fetch --quiet origin
git reset --hard "$REF"

composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader

npm ci --no-audit --no-fund
npm run build

php artisan migrate --force

php artisan optimize:clear
php artisan optimize

# Workers keep old code in memory until restarted (supervisor starts them again).
php artisan queue:restart
php artisan reverb:restart || true

echo "==> Deployed $(git rev-parse --short HEAD)"
