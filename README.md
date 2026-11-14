# WordPress FOSSASIA Event Plugin (WPFAevent)

The **FOSSASIA Event Plugin** provides WordPress integrations for [Eventyay](https://eventyay.com)-based events. It lets you display event landing pages, speakers, events, past events, schedules, and code of conduct content using classic page templates, Gutenberg blocks, or shortcodes. Shortcodes and blocks use embedded template rendering so WPFA content can live inside existing theme pages without the standalone template header/footer.

This plugin is maintained by [FOSSASIA](https://fossasia.org) and is compatible with the **eventyay** platform.

## Features

- Display **speakers**, **sessions**, and **event schedules** from Eventyay or other compatible APIs.
- Works with the unified **Eventyay (Django + Vue 3)** architecture.
- Includes an **admin settings page** to configure JSON API endpoints and cache duration.
- Supports WPFA template **shortcodes** for embedding event pages anywhere on your site.
- Provides Gutenberg **blocks** for the landing, speakers, events, past events, schedule, and code of conduct templates.
- Uses embedded template output for shortcodes and blocks so templates work in classic and block themes.
- Built with modern WordPress development practices:
  - Class‑based structure
  - Hooks and actions
  - Internationalization (translation‑ready)
- Includes **placeholder data** for local development and testing.
- Easily extendable with custom templates, endpoints, or additional shortcodes.

---

## Requirements

- WordPress **5.8** or higher
- PHP **7.4** or newer
- HTTPS‑enabled server (for API calls)
- The WordPress REST API and `wp_remote_get()` must be available

> **For contributors only:** Node.js >= 24 is required for the npm-based lint/format toolchain (`npm install`, `npm run lint`, etc.). It is not needed to install or use the plugin.

## Installation

1. **Clone the repository** into your WordPress `wp-content/plugins/` directory:
   ```bash
   git clone https://github.com/fossasia/WPFAevent.git event-plugin
   ```
2. **Install JavaScript dev tooling** *(contributors only — not required to use the plugin)*:
   ```bash
   # If you use nvm:
   nvm install 24 && nvm use 24
   npm install
   ```
3. Activate **Event Plugin** in the WordPress Admin under `Plugins → Installed Plugins → Event Plugin → Activate`.
4. Configure your API endpoints:
   - Go to **Settings → Event Plugin** in the WordPress Admin.
   - Enter the URLs of your Eventyay API endpoints for **Speakers**, **Sessions**, and **Schedule**.
   - Optionally adjust the **cache time (TTL)** in seconds.
5. Add WPFA template shortcodes to your pages or posts, for example:
   ```text
   [wpfaevent_landing]
   [wpfaevent_speakers]
   [wpfaevent_events]
   [wpfaevent_past_events]
   [wpfaevent_schedule]
   [wpfaevent_code_of_conduct]
   ```

   You can also add the matching WPFA blocks in the block editor.

## Development

| Script | Command | Description |
|--------|---------|-------------|
| `npm run lint` | `eslint .` | Lint all JavaScript files using the WordPress ESLint config |
| `npm run lint:fix` | `eslint . --fix` | Auto‑fix lint errors |
| `npm run format` | `prettier --write "**/*.{js,json}"` | Apply Prettier formatting |
| `npm run phpcs` | `composer phpcs` | Run PHP_CodeSniffer on PHP files |
| `npm run phpcbf` | `composer phpcbf` | Auto‑fix PHP coding‑standard violations |
| `npm run phpstan` | `composer phpstan` | Run PHPStan static analysis |
| `npm run check` | `npm run lint && npm run phpcs && npm run phpstan` | One‑command quality check (JS + PHP) |

> **Tip:** Before opening a Pull Request, run `npm run check` locally to ensure both JavaScript and PHP code meet the project’s standards.

## Directory Structure

```
event-plugin/
│
├─ event-plugin.php                 → main plugin file (entry point)
│
├─ includes/
│   ├─ class-event-loader.php       → initializes hooks and shortcodes
│   ├─ class-event-api.php          → handles remote API fetching with caching
│   ├─ class-event-admin.php        → admin settings page (API config, cache)
│   ├─ class-event-speakers.php     → logic for speakers shortcode
│   ├─ class-event-sessions.php     → logic for sessions shortcode
│   └─ class-event-schedule.php     → logic for schedule shortcode
│
├─ public/
│   ├─ partials/
│   │   ├─ event-speakers.php       → speaker display template
│   │   ├─ event-sessions.php       → sessions display template
│   │   └─ event-schedule.php       → schedule display template
│   ├─ css/
│   │   └─ event-public.css         → public-facing styles
│   └─ js/
│       ├─ event-public.js          → public-facing scripts
│       └─ event-admin.js           → admin JS for “Test Connection” buttons
│
├─ assets/
│   └─ img/
│       └─ speaker-placeholder.jpg  → placeholder image (no real data)
│
├─ languages/
│   └─ event-plugin.pot             → base translation template
│
└─ README.md
```


## Shortcodes Overview

| Shortcode                             | Description                                      | Output Source  |
| ------------------------------------- | ------------------------------------------------ | -------------- |
| `[wpfaevent_landing]`                 | Displays the WPFA event landing page.            | WPFA template  |
| `[wpfaevent_speakers]`                | Displays the speaker directory.                  | WPFA template  |
| `[wpfaevent_events]`                  | Displays the upcoming events hub.                | WPFA template  |
| `[wpfaevent_past_events]`             | Displays the past events archive.                | WPFA template  |
| `[wpfaevent_schedule]`                | Displays the event schedule.                     | WPFA template  |
| `[wpfaevent_code_of_conduct]`         | Displays the code of conduct content.            | WPFA template  |
| `[wpfaevent_template template="events"]` | Embeds a selected WPFA template by template key. | WPFA template |

**Note:** All shortcodes now accept an optional `align` attribute (`align="wide"` or `align="full"`). When provided, the shortcode output will be wrapped in a corresponding alignment container, e.g., `[wpfaevent_events align="full"]`. If omitted, the shortcode renders the raw embed without altering the surrounding layout.

The generic `wpfaevent_template` shortcode accepts a `template` attribute. Supported values include `landing`, `speakers`, `events`, `past_events`, `schedule`, and `code_of_conduct`.

## Blocks Overview

| Block Title              | Block Name                         |
| ------------------------ | ---------------------------------- |
| WPFA Landing             | `wpfaevent/landing`                |
| WPFA Speakers            | `wpfaevent/speakers`               |
| WPFA Events              | `wpfaevent/events`                 |
| WPFA Past Events         | `wpfaevent/past-events`            |
| WPFA Schedule            | `wpfaevent/schedule`               |
| WPFA Code of Conduct     | `wpfaevent/code-of-conduct`        |

WPFA blocks render dynamically and share the same embedded template mode as the shortcodes.

Each generic template shortcode can accept optional attributes, for example:

```text
[wpfaevent_template template="speakers"]
```

## Speaker Data Model

Speaker profiles use the `wpfa_speaker` custom post type, registered speaker metadata, and the event-speaker relationship fields `wpfa_event_speakers` and `wpfa_speaker_events`.

See [`docs/speaker-data-model.md`](docs/speaker-data-model.md) for the speaker fields, REST-exposed metadata, relationship sync behavior, and the interim session metadata approach.

## Settings Page

Navigate to **Settings → Event Plugin** to configure:

* **Speakers Endpoint:** `https://example.org/api/v1/events/{id}/speakers`
* **Sessions Endpoint:** `https://example.org/api/v1/events/{id}/sessions`
* **Schedule Endpoint:** `https://example.org/api/v1/events/{id}/schedule`
* **Cache TTL (seconds):** Duration for transient caching of API results
* **Test Buttons:** Verify that endpoints respond with valid JSON data

If the fields are left empty, the plugin falls back to placeholder content for development.

## Calendar Export And Timezones

The schedule page exposes an **Add to calendar** link when an event has a valid start date. The link opens a Google Calendar event template. Individual events can also be downloaded as `.ics` files from `/wp-json/wpfaevent/v1/events/{event_id}/ics`.

Event timezone behavior is deterministic:

* Each event can save an explicit timezone. If it is empty, WPFAevent falls back to the WordPress site timezone.
* All-day events export date-only `DTSTART` and exclusive date-only `DTEND` values.
* Timed events are interpreted in the event timezone and exported as UTC `DTSTART`/`DTEND` values.

## Development Notes

* Core logic resides in `includes/`, presentation templates in `public/partials/`.
* All user-facing text should use translation functions `__()` or `_e()`.
* Load assets using `wp_enqueue_script()` and `wp_enqueue_style()`.
* Use the built-in caching layer via transients in `class-event-api.php`.
* Do **not** commit large demo data or real images — use placeholders only.
* To modify the layout, you can override templates in your theme directory:

  ```
  your-theme/event-plugin/partials/event-speakers.php
  ```

  WordPress will automatically use the theme’s version if it exists.

---

## Local Development

### Option 1: Docker Setup (Recommended)

Docker Compose provides a reproducible environment for running automated checks, PHPUnit test suites, and an optional full local WordPress environment.

#### Prerequisites & Runtime Specifications

- [Docker Engine](https://docs.docker.com/engine/install/) (20.10+)
- [Docker Compose](https://docs.docker.com/compose/install/) (v2+)
- **PHP Runtime:** PHP 8.2 CLI / Apache with extensions `mysqli`, `pdo`, `pdo_mysql`, `zip`, `dom`, `mbstring`, `xml`, `xmlwriter`
- **Database:** MySQL 8.0 (bound to `127.0.0.1` for local machine security)
- **Test Framework:** PHPUnit with WordPress test suite (`WP_VERSION` defaults to `latest`, configurable)
- **Composer:** Composer 2.x

#### 1. Fast Checks & PHPUnit Tests (Default Workflow)

You do **not** need to start a full WordPress website to run tests or code quality checks. The isolated `test` container automatically manages Composer dependencies, bootstraps an isolated `wordpress_test` database, and caches test libraries in `test_cache`:

```bash
# Run PHPUnit test suite (auto-bootstraps test database on first run)
docker compose run --rm test

# Run a specific test suite
docker compose run --rm test composer test -- --testsuite Unit
docker compose run --rm test composer test -- --testsuite Integration

# Run WordPress Coding Standards (PHPCS)
docker compose run --rm test composer phpcs

# Auto-fix coding standard violations (PHPCBF)
docker compose run --rm test composer phpcbf

# Run static analysis (PHPStan)
docker compose run --rm test composer phpstan

# Pin a specific WordPress version for tests (optional, defaults to latest)
WP_VERSION=6.4 docker compose run --rm test
```

#### 2. Optional: Full WordPress Site (Manual Testing)

If you need a running WordPress instance to test admin pages, Gutenberg blocks, or frontend templates in your browser, opt-in to the `site` profile:

```bash
# 1. Copy the example configuration (optional)
cp .env.example .env

# 2. Start the site and database
docker compose --profile site up -d

# 3. Stream initialization logs until WordPress is ready
docker compose --profile site logs -f wp-cli
```

Once initialized:
- **Site Frontend:** [http://localhost:8080](http://localhost:8080)
- **Admin Dashboard:** [http://localhost:8080/wp-admin](http://localhost:8080/wp-admin) (Username: `admin`, Password set via `WP_ADMIN_PASSWORD` in `.env`, defaults to `admin`)
- **Plugin Settings:** Navigate to **Settings → Event Plugin**

To run WP-CLI commands against the local site container:
```bash
docker compose --profile site run --rm wp-cli wp <command>

# Example: Check active plugins
docker compose --profile site run --rm wp-cli wp plugin list

# Example: Run Eventyay import
docker compose --profile site run --rm wp-cli wp wpfa import
```

#### 3. Security, Caches & Troubleshooting

- **Local Network Isolation:** Exposed ports (`8080`, `3306`) bind strictly to `127.0.0.1` by default to prevent LAN access.
- **Credential Protection:** Access to `.env` files and `debug.log` is blocked by Apache security configurations and `.htaccess`. Debug logging is disabled by default (`WORDPRESS_DEBUG_LOG=0`).
- **Port Conflicts:** If `8080` or `3306` is already in use on your host, change `WORDPRESS_PORT` or `DB_PORT` in `.env`.
- **Resetting the Environment:** To stop all containers and wipe test/database volumes for a clean start:
  ```bash
  docker compose --profile site down -v
  ```

---

### Option 2: Manual Setup (Without Docker)

1. Install WordPress locally (e.g., using LocalWP, Docker, or WP-CLI).
2. Place this plugin in `wp-content/plugins/`.
3. Activate it and navigate to **Settings → Event Plugin**.
4. Test with public Eventyay JSON endpoints or your own mock data.
5. Use `wp wpfa import` again whenever you need to refresh imported events and speakers from the configured Eventyay source.

To debug API calls, enable WordPress debug logging in `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Logs can be found in `/wp-content/debug.log`.

## Translation

* The plugin is fully internationalization-ready (`Text Domain: event-plugin`).
* Translations are located in the `languages/` directory.
* You can generate `.mo` and `.po` files using tools such as **Poedit** or **Loco Translate**.

## Contributing

Contributions are welcome!

* Fork the repository on GitHub
* Create a feature branch:

  ```bash
  git checkout -b feature/my-feature
  ```
* Commit and push your changes, then submit a **Pull Request**
* Follow **WordPress PHP coding standards**

### Development commands

Commands can be run either inside the Docker container or directly on your host machine:

#### Via Docker (No local PHP/Composer setup needed)

```bash
# Run the test suite (auto-bootstraps test environment on first run)
docker compose run --rm test

# Run specific test suites
docker compose run --rm test composer test -- --testsuite Unit
docker compose run --rm test composer test -- --testsuite Integration

# Run code style checks (PHPCS)
docker compose run --rm test composer phpcs

# Auto-fix code style violations (PHPCBF)
docker compose run --rm test composer phpcbf

# Run static analysis (PHPStan)
docker compose run --rm test composer phpstan
```

#### On Host Machine (Direct)

```bash
# Install dev dependencies
composer install

# Run code style checks
composer phpcs

# Auto-fix code style violations
composer phpcbf

# Run static analysis
composer phpstan

# Bootstrap the WordPress test environment
# Requires WP_DB_USER and WP_DB_PASS env vars (defaults: wp_test / test_password)
composer setup-tests

# Run the test suite
composer test

# Run a specific test suite
composer test -- --testsuite Unit
composer test -- --testsuite Integration
```

Before submitting:

* Run `npm run check` to ensure both JS and PHP quality checks pass.
* Run `composer test` to ensure the full PHP test suite passes.
* Avoid committing binary or large files
* Test locally with caching disabled
* Ensure translations are wrapped correctly in `__()` or `_e()`

## License

Licensed under the **Apache License, Version 2.0**
Copyright © 2025 [FOSSASIA](https://fossasia.org)
