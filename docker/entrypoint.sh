#!/bin/sh
set -e

echo "Waiting for database"
until nc -z -v -w30 $DB_HOST $DB_PORT; do
  echo "Database is unavailable - sleeping"
  sleep 1
done
echo "Database is up!"

echo "Running migrations"
php artisan migrate --force

if [ $# -gt 0 ]; then
    echo "Executing command: $@"
    exec "$@"
else
    echo "Starting PHP-FPM"
    exec php-fpm
fi
