#!/bin/sh
set -e

echo "Syncing application assets..."
mkdir -p public/build
cp -ar /usr/src/app/vendor/. ./vendor/
cp -ar /usr/src/app/public/build/. ./public/build/

# If .env doesn't exist, create it from example
if [ ! -f .env ]; then
    echo ".env file not found, creating from .env.example"
    if [ -f .env.example ]; then
        cp .env.example .env
    else
        touch .env
    fi
fi

# Only generate key if it's not already set in .env
if ! grep -q "^APP_KEY=base64:" .env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Export the key for the current process
export APP_KEY=$(grep "^APP_KEY=" .env | tail -n 1 | cut -d'=' -f2-)
echo "APP_KEY exported successfully"

echo "Waiting for database"
until nc -z -v -w30 $DB_HOST $DB_PORT; do
  echo "Database is unavailable - sleeping"
  sleep 1
done
echo "Database is up!"

echo "Clearing cached configuration"
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Running migrations"
php artisan migrate:fresh --seed

if [ $# -gt 0 ]; then
    echo "Executing command: $@"
    exec "$@"
else
    echo "Starting PHP-FPM"
    exec php-fpm
fi
