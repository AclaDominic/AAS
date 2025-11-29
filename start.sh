#!/bin/bash

# Start script for production server deployment
echo "Starting AAS application..."

# Determine the base directory (works in both /app and current directory)
if [ -d "/app" ]; then
  BASE_DIR="/app"
else
  BASE_DIR="$(pwd)"
fi

BACKEND_DIR="$BASE_DIR/backend"

# Check if backend directory exists
if [ ! -d "$BACKEND_DIR" ]; then
  echo "Error: $BACKEND_DIR directory not found!"
  echo "Current directory: $(pwd)"
  echo "Contents: $(ls -la)"
  exit 1
fi

# Set default PORT if not provided
if [ -z "$PORT" ]; then
  PORT=8000
  echo "Warning: PORT environment variable not set, defaulting to $PORT"
fi

# Check and generate APP_KEY if missing
cd "$BACKEND_DIR"
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
  echo "APP_KEY not set, generating..."
  php artisan key:generate --force
fi

# Ensure storage and cache directories are writable
echo "Setting up storage permissions..."
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Test database connection before caching (if DB is configured)
if [ ! -z "$DB_CONNECTION" ] && [ "$DB_CONNECTION" != "" ]; then
  echo "Testing database connection..."
  php artisan db:show 2>&1 || echo "Database connection test failed (this is OK if migrations haven't been run yet)"
fi

# Clear old caches first to avoid stale config
echo "Clearing old caches..."
php artisan config:clear 2>&1 || true
php artisan route:clear 2>&1 || true
php artisan view:clear 2>&1 || true

# Cache configuration
echo "Caching Laravel configuration..."
php artisan config:cache 2>&1 || echo "Config cache failed"
php artisan route:cache 2>&1 || echo "Route cache failed"
php artisan view:cache 2>&1 || echo "View cache failed"

# Show recent errors if log file exists
if [ -f "storage/logs/laravel.log" ]; then
  echo "Recent Laravel errors (last 20 lines):"
  tail -n 20 storage/logs/laravel.log 2>/dev/null || echo "Could not read log file"
fi

# Start queue listener in background (only if QUEUE_CONNECTION is set)
if [ ! -z "$QUEUE_CONNECTION" ] && [ "$QUEUE_CONNECTION" != "sync" ]; then
  echo "Starting queue listener..."
  (cd "$BACKEND_DIR" && php artisan queue:work --tries=3 --timeout=90) &
  QUEUE_PID=$!
else
  echo "Queue listener skipped (QUEUE_CONNECTION is sync or not set)"
  QUEUE_PID=""
fi

# Start scheduler in background (runs every minute)
echo "Starting scheduler..."
(
  while true; do
    cd "$BACKEND_DIR" && php artisan schedule:run --verbose --no-interaction
    sleep 60
  done
) &
SCHEDULER_PID=$!

# Function to cleanup background processes on exit
cleanup() {
  echo "Shutting down background processes..."
  [ ! -z "$QUEUE_PID" ] && kill $QUEUE_PID 2>/dev/null || true
  [ ! -z "$SCHEDULER_PID" ] && kill $SCHEDULER_PID 2>/dev/null || true
  wait 2>/dev/null || true
  exit
}

trap cleanup SIGTERM SIGINT EXIT

echo "Starting Laravel server on port $PORT..."
cd "$BACKEND_DIR" && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
