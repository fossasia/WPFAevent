# WordPress FOSSASIA Event Plugin (WPFAevent)

The **FOSSASIA Event Plugin** provides WordPress integrations for [Eventyay](https://eventyay.com)-based events.  
It allows you to display event sessions, speakers, and schedules directly on WordPress pages using **shortcodes**, **manual content**, or **custom templates**.

This plugin is maintained by [FOSSASIA](https://fossasia.org) and is compatible with the **eventyay** platform.

## Features

- Display **speakers**, **sessions**, and **event schedules** from Eventyay or other compatible APIs.
- Works with the unified **Eventyay (Django + Vue 3)** architecture.
- Includes an **admin settings page** to configure JSON API endpoints and cache duration.
- Supports **shortcodes** for embedding event data anywhere on your site.
- Built with modern WordPress development practices:
  - Class-based structure
  - Hooks and actions
  - Internationalization (translation-ready)
- Includes **placeholder data** for local development and testing.
- Easily extendable with custom templates, endpoints, or additional shortcodes.

---

## Requirements

- WordPress **5.8** or higher  
- PHP **7.4** or newer  
- HTTPS-enabled server (for API calls)
- The WordPress REST API and `wp_remote_get()` must be available

## Installation

1. Download or clone this plugin into your WordPress `wp-content/plugins/` directory:
   ```bash
   git clone https://github.com/fossasia/WPFAevent.git event-plugin


2. Activate **Event Plugin** in your WordPress Admin under
   `Plugins → Installed Plugins → Event Plugin → Activate`.

3. Configure your API endpoints:

   * Go to **Settings → Event Plugin** in the WordPress Admin.
   * Enter the URLs of your Eventyay API endpoints for **Speakers**, **Sessions**, and **Schedule**.
   * Optionally adjust the **cache time (TTL)** in seconds.

4. Add shortcodes to your pages or posts, for example:

   ```text
   [event_speakers]
   [event_sessions]
   [event_schedule]
   ```

   These will automatically display data fetched from your configured endpoints.
   If no API data is available, placeholder content will appear instead.

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

| Shortcode          | Description                                             | Output Source               |
| ------------------ | ------------------------------------------------------- | --------------------------- |
| `[event_speakers]` | Displays the list of speakers.                          | API endpoint or placeholder |
| `[event_sessions]` | Displays event sessions with title, time, and abstract. | API endpoint or placeholder |
| `[event_schedule]` | Displays daily schedule in a table format.              | API endpoint or placeholder |

The seeder command is **idempotent**, meaning it is safe to re-run. It will update existing posts based on their slugs instead of creating duplicates.

###  Minimal Demo Data

To generate sample data for testing, use:

```bash
wp wpfa seed-minimal
```

if multiple event profiles are configured in settings.


## Settings Page

Navigate to **Settings → Event Plugin** to configure:

* **Speakers Endpoint:** `https://example.org/api/v1/events/{id}/speakers`
* **Sessions Endpoint:** `https://example.org/api/v1/events/{id}/sessions`
* **Schedule Endpoint:** `https://example.org/api/v1/events/{id}/schedule`
* **Cache TTL (seconds):** Duration for transient caching of API results
* **Test Buttons:** Verify that endpoints respond with valid JSON data

If the fields are left empty, the plugin falls back to placeholder content for development.

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

1. Install WordPress locally (e.g., using LocalWP, Docker, or WP-CLI).
2. Place this plugin in `wp-content/plugins/`.
3. Activate it and navigate to **Settings → Event Plugin**.
4. Test with public Eventyay JSON endpoints or your own mock data.

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

Before submitting:

* Run `phpcs` with the WordPress standard
* Avoid committing binary or large files
* Test locally with caching disabled
* Ensure translations are wrapped correctly in `__()` or `_e()`

## License

Licensed under the **Apache License, Version 2.0**
Copyright © 2025 [FOSSASIA](https://fossasia.org)
=======
The seeder command is **idempotent**, meaning it is safe to re-run. It will update existing posts based on their slugs instead of creating duplicates.

###  Minimal Demo Data

To generate sample data for testing, use:

```bash
wp wpfa seed-minimal
```

This will:
- Create 1 demo event
- Create 2 demo speakers with placeholder images (from via.placeholder.com)

No real speaker photos or private data are included.
>>>>>>> Stashed changes
