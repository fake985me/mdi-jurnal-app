#!/usr/bin/env bash
# Exit on error
set -o errexit

echo "🚀 Starting Render.com Build Process..."

# Update package manager and install required tools
echo "📦 Installing system dependencies..."
apt-get update -qq
apt-get install -y -qq postgresql-client

# Install Composer dependencies
echo "🎼 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Install Node.js dependencies
echo "📦 Installing Node.js dependencies..."
npm ci --only=production

# Build frontend assets with Vite
echo "🏗️  Building frontend assets..."
npm run build

# Clear Laravel caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Optimize Laravel for production
echo "⚡ Optimizing Laravel for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
echo "🔗 Creating storage symlink..."
php artisan storage:link || true

# Set proper permissions
echo "🔒 Setting file permissions..."
chmod -R 755 storage bootstrap/cache

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force --no-interaction

# Optional: Seed database (uncomment if needed)
# echo "🌱 Seeding database..."
# php artisan db:seed --force --no-interaction

# Clear and optimize after migration
echo "✨ Final optimization..."
php artisan optimize

echo "✅ Build completed successfully!"
