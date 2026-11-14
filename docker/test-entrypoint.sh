#!/bin/bash
set -e

# Avoid git dubious ownership warning inside container
git config --global --add safe.directory /app 2>/dev/null || true

# Disable SSL verification for MariaDB client connecting to local MySQL
if [ ! -f /etc/my.cnf.d/skip-ssl.cnf ]; then
    mkdir -p /etc/my.cnf.d
    printf "[client]\nssl=0\nskip-ssl-verify-server-cert\n" > /etc/my.cnf.d/skip-ssl.cnf
fi

# If running composer test or phpunit, ensure WordPress test suite is initialized
if [ "$1" = "composer" ] && [ "$2" = "test" ] || [ "$1" = "vendor/bin/phpunit" ]; then
    if [ ! -f "/tmp/wordpress-tests-lib/includes/functions.php" ]; then
        echo "WordPress test suite not found. Bootstrapping test environment..."
        composer setup-tests
    fi
fi

exec "$@"
