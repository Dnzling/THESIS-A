#!/usr/bin/env bash
set -euo pipefail

BRANCH="${1:-develop}"

echo "==> Deploying branch: ${BRANCH}"

if ! command -v git >/dev/null 2>&1; then
  echo "Error: git is required on the server."
  exit 1
fi

echo "==> Pulling latest code"
git fetch origin "${BRANCH}"
git checkout "${BRANCH}"
git pull --ff-only origin "${BRANCH}"

if command -v composer >/dev/null 2>&1; then
  echo "==> Installing PHP dependencies"
  composer install --no-dev --optimize-autoloader --no-interaction
else
  echo "Warning: composer not found. Skipping composer install."
fi

if command -v php >/dev/null 2>&1; then
  echo "==> Ensuring storage symlink exists"
  php artisan storage:link || true

  echo "==> Running database migrations"
  php artisan migrate --force

  echo "==> Removing Vite hot file (production safety)"
  rm -f public/hot

  echo "==> Optimizing Laravel caches"
  php artisan optimize:clear
  php artisan config:cache
  php artisan view:cache
  php artisan optimize
else
  echo "Error: php is required on the server."
  exit 1
fi

if command -v pnpm >/dev/null 2>&1; then
  echo "==> Building frontend assets with pnpm"
  pnpm install --frozen-lockfile
  pnpm run build
elif command -v npm >/dev/null 2>&1; then
  echo "==> Building frontend assets with npm"
  npm ci
  npm run build
else
  if [[ -f public/build/manifest.json ]]; then
    echo "==> Node tooling not found; using committed public/build assets."
  else
    echo "Warning: Node tooling not found and public/build/manifest.json is missing."
    echo "Warning: Frontend assets may be out of date."
  fi
fi

echo "==> Deployment complete"
