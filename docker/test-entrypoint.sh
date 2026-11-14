#!/bin/bash
set -e

# Avoid git dubious ownership warning inside container
git config --global --add safe.directory /app 2>/dev/null || true

# Disable SSL verification for MariaDB client connecting to local MySQL
if [ ! -f /etc/my.cnf.d/skip-ssl.cnf ]; then
    mkdir -p /etc/my.cnf.d
    printf "[client]\nssl=0\nskip-ssl-verify-server-cert\n" > /etc/my.cnf.d/skip-ssl.cnf
fi

# Ensure Composer dependencies are installed if missing in mounted directory
if [ ! -f "/app/vendor/autoload.php" ]; then
    echo "Composer dependencies not found in /app/vendor. Installing..."
    composer install --no-interaction --prefer-dist
fi

# If running composer test or phpunit, ensure WordPress test suite and database are initialized
if [ "$1" = "composer" ] && [ "$2" = "test" ] || [ "$1" = "vendor/bin/phpunit" ]; then
    TEST_DB="${WP_TEST_DB_NAME:-wordpress_test}"

    # Serialize bootstrap to prevent concurrent test runs from conflicting
    LOCK_DIR="/tmp/.wp-test-bootstrap.lock"
    retries=0
    while ! mkdir "$LOCK_DIR" 2>/dev/null; do
        echo "Another container is initializing the test environment. Waiting..."
        retries=$((retries + 1))
        if [ "$retries" -ge 30 ]; then
            echo "Timed out waiting for test bootstrap lock." >&2
            exit 1
        fi
        sleep 2
    done
    trap 'rmdir "$LOCK_DIR" 2>/dev/null || true' EXIT

    DB_READY=0
    if [ -f "/tmp/wordpress-tests-lib/includes/functions.php" ]; then
        if mariadb -h "$WP_DB_HOST" -u "$WP_DB_USER" -p"$WP_DB_PASS" -e "USE ${TEST_DB};" >/dev/null 2>&1; then
            DB_READY=1
        fi
    fi

    if [ "$DB_READY" -eq 0 ]; then
        echo "WordPress test suite or database not ready. Bootstrapping test environment..."
        composer setup-tests
    fi

    rmdir "$LOCK_DIR" 2>/dev/null || true
    trap - EXIT
fi

exec "$@"
