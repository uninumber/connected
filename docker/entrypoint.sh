#!/bin/sh
set -e

# Generate APP_KEY if it's not set
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generating one..."
    # Use a temporary .env file just for the key generation if it doesn't exist
    if [ ! -f .env ]; then
        touch .env
    fi
    php artisan key:generate --force
    # Export the newly generated key for the current process
    export APP_KEY=$(grep APP_KEY .env | cut -d'=' -f2)
fi

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
php artisan migrate --force

if [ $# -gt 0 ]; then
    echo "Executing command: $@"
    exec "$@"
else
    echo "Starting PHP-FPM"
    exec php-fpm
fi
