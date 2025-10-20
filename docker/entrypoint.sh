#!/bin/bash
set -e

# Ensure Laravel writable directories exist
mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache

# Detect if we're running as root or www-data
CURRENT_UID=$(id -u)

if [ "$CURRENT_UID" = "0" ]; then
  echo "Fixing permissions for Laravel directories..."
  chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
  chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
else
  echo "Running as non-root (UID: $CURRENT_UID), skipping chown..."
  chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
fi

# Mark repo safe for Composer if needed
git config --global --add safe.directory /var/www/html || true

# Run CMD
exec "$@"
