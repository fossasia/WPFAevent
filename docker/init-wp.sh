#!/bin/sh
set -e

# If arguments are passed to wp-cli (e.g. `docker compose run --rm wp-cli wp ...`), execute them directly
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

echo "Waiting for WordPress files and wp-config.php..."
while [ ! -f /var/www/html/wp-config.php ]; do
    sleep 2
done

echo "Waiting for MySQL database connection..."
until wp db query "SELECT 1" --path=/var/www/html --quiet 2>/dev/null; do
    sleep 2
done

echo "Checking if WordPress is already installed..."
if wp core is-installed --path=/var/www/html 2>/dev/null; then
    echo "WordPress is already installed."
    wp plugin activate wpfaevent --path=/var/www/html || true
    exit 0
fi

echo "Installing WordPress core..."
wp core install \
    --path=/var/www/html \
    --url="${WP_URL:-http://localhost:8080}" \
    --title="${WP_TITLE:-WPFA Event Dev}" \
    --admin_user="${WP_ADMIN_USER:-admin}" \
    --admin_password="${WP_ADMIN_PASSWORD:-admin}" \
    --admin_email="${WP_ADMIN_EMAIL:-admin@example.local}" \
    --skip-email

echo "Configuring permalink structure..."
wp rewrite structure '/%postname%/' --path=/var/www/html

echo "Activating wpfaevent plugin..."
wp plugin activate wpfaevent --path=/var/www/html

echo "=================================================="
echo " WordPress is ready: ${WP_URL:-http://localhost:8080}"
echo " Admin User: ${WP_ADMIN_USER:-admin}"
echo " Admin Password: ${WP_ADMIN_PASSWORD:-admin}"
echo "=================================================="
