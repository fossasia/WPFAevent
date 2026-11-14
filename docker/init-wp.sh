#!/bin/sh
set -e

# If arguments are passed to wp-cli (e.g. `docker compose run --rm wp-cli wp ...`), execute them directly
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

WP_PORT="${WORDPRESS_PORT:-8080}"
DEFAULT_WP_URL="http://localhost:${WP_PORT}"
SITE_URL="${WP_URL:-$DEFAULT_WP_URL}"
MAX_RETRIES=30

echo "Waiting for WordPress files and wp-config.php..."
retries=0
while [ ! -f /var/www/html/wp-config.php ]; do
    retries=$((retries + 1))
    if [ "$retries" -ge "$MAX_RETRIES" ]; then
        echo "Error: Timed out waiting for /var/www/html/wp-config.php after ${MAX_RETRIES} attempts." >&2
        exit 1
    fi
    sleep 2
done

echo "Waiting for MySQL database connection..."
retries=0
until wp db query "SELECT 1" --path=/var/www/html --quiet 2>/dev/null; do
    retries=$((retries + 1))
    if [ "$retries" -ge "$MAX_RETRIES" ]; then
        echo "Error: Timed out waiting for MySQL database connection after ${MAX_RETRIES} attempts. Last error:" >&2
        wp db query "SELECT 1" --path=/var/www/html || true
        exit 1
    fi
    sleep 2
done

# Serialize initialization to prevent concurrent initializers from racing
LOCK_DIR="/var/www/html/.wp-init.lock"
retries=0
while ! mkdir "$LOCK_DIR" 2>/dev/null; do
    echo "Another initialization process is currently running. Waiting..."
    retries=$((retries + 1))
    if [ "$retries" -ge "$MAX_RETRIES" ]; then
        echo "Error: Timed out waiting for initialization lock." >&2
        exit 1
    fi
    sleep 2
done
trap 'rmdir "$LOCK_DIR" 2>/dev/null || true' EXIT

echo "Checking if WordPress is already installed..."
if wp core is-installed --path=/var/www/html 2>/dev/null; then
    echo "WordPress is already installed."
    wp plugin activate wpfaevent --path=/var/www/html
    rmdir "$LOCK_DIR" 2>/dev/null || true
    trap - EXIT
    exit 0
fi

echo "Installing WordPress core..."
wp core install \
    --path=/var/www/html \
    --url="${SITE_URL}" \
    --title="${WP_TITLE:-WPFA Event Dev}" \
    --admin_user="${WP_ADMIN_USER:-admin}" \
    --admin_password="${WP_ADMIN_PASSWORD:-admin}" \
    --admin_email="${WP_ADMIN_EMAIL:-admin@example.local}" \
    --skip-email

echo "Configuring permalink structure..."
wp rewrite structure '/%postname%/' --path=/var/www/html

echo "Activating wpfaevent plugin..."
wp plugin activate wpfaevent --path=/var/www/html

rmdir "$LOCK_DIR" 2>/dev/null || true
trap - EXIT

echo "=================================================="
echo " WordPress is ready: ${SITE_URL}"
echo " Admin User: ${WP_ADMIN_USER:-admin}"
echo " (Password set via WP_ADMIN_PASSWORD)"
echo "=================================================="
