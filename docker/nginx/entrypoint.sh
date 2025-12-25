#!/bin/sh
set -e

echo "Waiting for application files to be available..."

MAX_WAIT=30
ELAPSED=0
CHECK_INTERVAL=1

while [ $ELAPSED -lt $MAX_WAIT ]; do
    if [ -f /var/www/public/index.php ] && [ -f /var/www/public/build/manifest.json ]; then
        echo "Application files are ready!"
        break
    fi

    if [ $ELAPSED -eq 0 ]; then
        echo "Waiting for /var/www/public/index.php and /var/www/public/build/manifest.json..."
    fi

    sleep $CHECK_INTERVAL
    ELAPSED=$((ELAPSED + CHECK_INTERVAL))

    if [ $((ELAPSED % 5)) -eq 0 ]; then
        echo "Still waiting... (${ELAPSED}s elapsed)"
    fi
done

if [ ! -f /var/www/public/index.php ]; then
    echo "ERROR: Timed out waiting for application files!"
    echo "The app container may not have synced files correctly."
    exit 1
fi

echo "Starting Nginx..."

# Execute the original nginx entrypoint with all arguments
exec /docker-entrypoint.sh "$@"
