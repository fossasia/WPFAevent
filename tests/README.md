# Test Suite Documentation

This repository contains a **PHPUnit** test suite for the **wpfaevent** WordPress plugin.

## Prerequisites

- **PHP** >= 8.1 (recommended 8.2)
- **Composer** installed globally (`composer.phar`)
- **MySQL** / **MariaDB** server running locally
- **WP-CLI** (optional but handy for scaffolding the test environment)

## Setting Up the Local Testing Environment

1. **Install dev dependencies**
   ```bash
   composer install
   ```

2. **Create a temporary WordPress testing installation**
   The bootstrap script looks for the WordPress test framework in the following locations:
   - `WP_TESTS_DIR` environment variable (e.g. `/tmp/wordpress-tests-lib`)
   - `WP_DEVELOP_DIR`/`tests/phpunit` (used when you have a local copy of the WordPress source)
   - Fallback to the system temporary directory.

   #### The easiest way is to use the project-provided script:
   ```bash
   composer setup-tests
   ```
   This command runs the `bin/install-wp-tests.sh` script, which:
   - Downloads the WordPress core into a temporary directory.
   - Creates a fresh database (default `wordpress_test`). You can adjust credentials via `phpunit.xml.dist` or environment variables.
   - Sets the `WP_TESTS_DIR` environment variable for the current shell session.


   If you prefer to run the script manually, you can invoke the bundled installer directly:
   ```bash
   bin/install-wp-tests.sh wordpress_test wp_test test_password 127.0.0.1 latest
   ```
   Adjust the DB credentials as needed. This does the same work as `composer setup-tests` but gives you explicit control over the parameters.


3. **Configure environment variables (optional)**
   You can override the default DB credentials without editing `phpunit.xml.dist`:
   ```bash
   export WP_DB_NAME=wordpress_test
   export WP_DB_USER=wp_test
   export WP_DB_PASS=test_password
   export WP_DB_HOST=127.0.0.1
   ```

## Running the Tests

```bash
composer test
# or directly
vendor/bin/phpunit
```

The test suite boots the WordPress testing framework, loads the `wpfaevent.php` plugin, and runs the sample test located in `tests/unit/SampleTest.php`.

## Adding New Tests

- Place unit test files under the `tests/unit/` directory with the `Test.php` suffix (e.g. `MyClassTest.php`).
- Place integration test files under the `tests/integration/` directory with the `IntegrationTest.php` suffix.
- Extend `WP_UnitTestCase` for access to factories, REST utilities, and other WordPress testing helpers.
- Run a specific suite: `vendor/bin/phpunit --testsuite Unit` or `vendor/bin/phpunit --testsuite Integration`.

## Common Issues & Troubleshooting

- **Could not find .../includes/functions.php** - Ensure `WP_TESTS_DIR` points at a valid WordPress test library. Rerun the install script or set `WP_DEVELOP_DIR`.
- **Database connection errors (ERROR 1698)** - On Ubuntu 20.04+ with MySQL 8.0, the `root` user uses `auth_socket` authentication. The `-p` flag is ignored and password-based login fails. Either switch root to `mysql_native_password` auth, or create a dedicated test user:

  ```bash
  # Option A: Switch root to password auth (for local dev only)
  sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'your_password';"
  sudo mysql -e "FLUSH PRIVILEGES;"

  # Option B: Create a dedicated test user (preferred)
  sudo mysql -e "CREATE USER IF NOT EXISTS 'wp_test'@'localhost' IDENTIFIED BY 'test_password';"
  sudo mysql -e "GRANT ALL PRIVILEGES ON wordpress_test.* TO 'wp_test'@'localhost';"
  sudo mysql -e "FLUSH PRIVILEGES;"

  # Then run with custom credentials:
  WP_DB_USER=wp_test WP_DB_PASS=test_password composer setup-tests
  ```
- **ERROR: Access denied for user 'root'@'localhost'** - Same root cause as above. See the MySQL auth_socket workaround.
- **gzip: stdin: not in gzip format** - The `WP_VERSION` environment variable may be set in your shell, overriding the script's default (`latest`). Unset it before running:

  ```bash
  unset WP_VERSION
  composer setup-tests
  ```
- **PHP version mismatch** – This project targets PHPUnit 9.6, which requires PHP ≥ 7.3. Adjust your PHP version or downgrade the testing deps.

Happy testing! 🎉
