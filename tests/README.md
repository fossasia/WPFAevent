# Test Suite Documentation

This repository contains a **PHPUnit** test suite for the **wpfaevent** WordPress plugin.

## Prerequisites

- **PHP** >= 8.1 (recommended 8.2)
- **Composer** installed globally (`composer.phar`)
- **MySQL** / **MariaDB** server running locally
- **WP‑CLI** (optional but handy for scaffolding the test environment)

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

   #### The easiest way is to use the project‑provided script:
   ```bash
   composer setup-tests
   ```
   This command runs the `bin/install-wp-tests.sh` script, which:
   - Downloads the WordPress core into a temporary directory.
   - Creates a fresh database (default `wordpress_test`). You can adjust credentials via `phpunit.xml.dist` or environment variables.
   - Sets the `WP_TESTS_DIR` environment variable for the current shell session.


   If you prefer to run the script manually, you can invoke the bundled installer directly:
   ```bash
   bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest
   ```
   Adjust the DB credentials as needed. This does the same work as `composer setup-tests` but gives you explicit control over the parameters.


3. **Configure environment variables (optional)**
   You can override the default DB credentials without editing `phpunit.xml.dist`:
   ```bash
   export WP_DB_NAME=wordpress_test
   export WP_DB_USER=root
   export WP_DB_PASS=root
   export WP_DB_HOST=127.0.0.1
   ```

## Running the Tests

```bash
composer test
# or directly
vendor/bin/phpunit
```

The test suite boots the WordPress testing framework, loads the `wpfaevent.php` plugin, and runs the sample test located in `tests/test-sample.php`.

## Adding New Tests

- Place new test files under the `tests/` directory.
- Follow the naming convention `test-*.php` (the `<directory>` element in `phpunit.xml.dist` already filters by that pattern).
- Extend `WP_UnitTestCase` for access to factories, REST utilities, and other WordPress testing helpers.

## Common Issues & Troubleshooting

- **Could not find .../includes/functions.php** – Ensure `WP_TESTS_DIR` points at a valid WordPress test library. Rerun the install script or set `WP_DEVELOP_DIR`.
- **Database connection errors** – Verify the DB credentials match a MySQL instance that allows a new database to be created.
- **PHP version mismatch** – This project targets PHPUnit 10, which requires PHP ≥ 8.1. Adjust your PHP version or downgrade the testing deps.

---

### Automated Upstream Script Checks

This repository includes a GitHub Actions workflow (`.github/workflows/check-install-wp-tests.yml`) that runs **once a month**. It compares the local `bin/install-wp-tests.sh` script against the upstream version from the WP‑CLI scaffold command. If a difference is detected, the workflow automatically creates a commit on a temporary branch and opens a Pull Request, allowing maintainers to review and merge the update. No direct pushes to `main` occur.

**How it works:**
- The workflow fetches the latest upstream script.
- It computes a SHA‑256 checksum and compares it to the committed script.
- When a mismatch is found, a PR is opened using `peter-evans/create-pull-request`.

You can also trigger the check manually via **GitHub → Actions → Check install‑wp‑tests → Run workflow**.

For developers who prefer to update the script themselves, simply run `composer setup-tests` which will pull the latest version.

Happy testing! 🎉
