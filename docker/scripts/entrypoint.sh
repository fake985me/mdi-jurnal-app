#!/bin/sh

# Exit on error
set -e

echo "🚀 Starting application setup..."

# Wait for database to be ready
echo "⏳ Waiting for database..."
while ! nc -z mysql 3306; do
  sleep 1
done
echo "✅ Database is ready!"

# Generate application key if not exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
    php artisan key:generate
fi

# Run migrations
echo "🗄️  Running migrations..."
php artisan migrate --force

# Clear and cache config
echo "🔧 Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
echo "🔒 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "✅ Application setup completed!"

# Execute the main command
exec "$@"
