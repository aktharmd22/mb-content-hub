#!/usr/bin/env bash
# One-step deploy: pull latest code, run migrations, clear all stale caches.
# Usage (from the project root):  bash deploy.sh
set -e

cd "$(dirname "$0")"

echo "→ Pulling latest code..."
git pull

echo "→ Running post-deploy steps..."
php artisan deploy

echo "✅ Done."
