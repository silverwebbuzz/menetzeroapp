#!/bin/bash

# Production Deployment Script for MenetZero (shared hosting friendly)
# Target path: /home/silverwebbuzz_in/public_html/menetzero/app
# Ensure your subdomain/app points DocumentRoot to: /home/silverwebbuzz_in/public_html/menetzero/app/public

set -euo pipefail

echo "Starting MenetZero deployment..."

# Move to the directory where this script resides
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

# Use server PHP binary if provided via env, otherwise fall back to default
PHP_BIN=${PHP_BIN:-php}

# Non-interactive composer mode and allow superuser for cron/automation environments
export COMPOSER_ALLOW_SUPERUSER=1

# Ensure Composer is available (global, local composer.phar, or download locally)
if command -v composer >/dev/null 2>&1; then
COMPOSER_CMD="composer"
elif [ -f "$SCRIPT_DIR/composer.phar" ]; then
COMPOSER_CMD="$PHP_BIN $SCRIPT_DIR/composer.phar"
else
echo "Composer not found. Downloading composer.phar locally..."
$PHP_BIN -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
$PHP_BIN composer-setup.php --quiet
rm -f composer-setup.php
COMPOSER_CMD="$PHP_BIN $SCRIPT_DIR/composer.phar"
fi

# Create .env from example if missing
if [ ! -f .env ]; then
cp .env.example .env || true
fi

# Ensure APP_URL is set to your domain
if grep -q "^APP_URL=" .env; then
sed -i 's#^APP_URL=.*#APP_URL=https://app.menetzero.com#' .env
else
echo "APP_URL=https://app.menetzero.com" >> .env
fi

# Ensure storage and cache directories exist and are writable
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache
chmod -R 775 storage bootstrap/cache || true

# Ensure .htaccess files exist
if [ ! -f public/.htaccess ]; then
cat > public/.htaccess <<'HTACC'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%1]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

<IfModule !mod_rewrite.c>
    ErrorDocument 404 /index.php
</IfModule>
HTACC
fi

if [ ! -f .htaccess ]; then
cat > .htaccess <<'ROOTACC'
<IfModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{REQUEST_URI} !^/public/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ public/index.php [L]
</IfModule>
ROOTACC
fi

# Install production dependencies only (non-interactive)
$COMPOSER_CMD install --no-dev --prefer-dist --no-interaction --no-ansi --optimize-autoloader

# Generate application key if not set
if ! grep -q "^APP_KEY=base64:" .env; then
$PHP_BIN artisan key:generate --force --no-ansi
fi

# Link storage (ignore error if already exists)
$PHP_BIN artisan storage:link || true

# Clear caches before rebuilding
$PHP_BIN artisan optimize:clear --no-ansi || true

# Cache configuration, routes, and views
$PHP_BIN artisan config:cache --no-ansi
$PHP_BIN artisan route:cache --no-ansi
$PHP_BIN artisan view:cache --no-ansi

# Run database migrations (make sure DB_* variables are correctly set in .env)
$PHP_BIN artisan migrate --force --no-ansi || true

# Final optimize
$PHP_BIN artisan optimize --no-ansi

echo "Deployment completed successfully!"
echo "Your application is ready at: https://app.menetzero.com"
