#!/bin/bash

# Production Deployment Script for MenetZero
# Run this script on your production server

echo "Starting MenetZero deployment..."

# Set proper permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# Install production dependencies only
composer install --no-dev --optimize-autoloader

# Clear and cache configuration
php artisan config:clear
php artisan config:cache

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Clear and cache views
php artisan view:clear
php artisan view:cache

# Run database migrations
php artisan migrate --force

# Clear application cache
php artisan cache:clear

# Optimize for production
php artisan optimize

echo "Deployment completed successfully!"
echo "Your application is ready at: https://app.menetzero.com"
