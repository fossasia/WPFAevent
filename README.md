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

3. Configure Eventyay import:

   * Go to **Events → Import Events** in the WordPress Admin.
   * Enter the Eventyay base URL, organizer slug, optional event slug, and API token.
   * Choose the imported event post status, then run the import.

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

Each shortcode can accept optional attributes — for example:

```text
[event_schedule profile="summit2026"]
```

if multiple event profiles are configured in settings.


## Roles And Permissions

WPFAevent uses three access levels. WordPress user roles stay unchanged.

| Access level | Publish events & speakers | Import/update from Eventyay | Edit existing content | Delete content |
| --- | --- | --- | --- | --- |
| **Administrator** | Yes | Yes | Yes | Yes |
| **Event Organizer** | Yes | Yes | Yes | Yes |
| **Event Contributor** | No | No | Yes | No |

Site administrators assign access under **WPFAEvent → Settings → Event Plugin Access**.

* **Administrator** — full WordPress site control.
* **Event Organizer** — run Eventyay import/update, publish new events and speakers, and access **WPFAEvent → Settings**.
* **Event Contributor** — maintain existing event and speaker details from wp-admin and the frontend dashboard, without import, publish, or delete access.

Site-wide footer branding remains administrator-only.

## Settings And Import Pages

Navigate to **WPFAEvent → Settings** for plugin-level settings and the future admin dashboard placeholder. Eventyay event imports are configured under **Events → Import Events**.

The Eventyay import page accepts:

* **Eventyay base URL:** The Eventyay site root, for example `https://eventyay.com`
* **Organizer slug:** The organizer path segment
* **Event slug:** Optional single-event filter
* **API token:** Optional private token for authenticated Eventyay endpoints
* **Imported post status:** Draft, published, pending review, or private

## Eventyay Event Import

Users with the **Event Organizer** role or **Administrator** role can import Eventyay events from **Events → Import Events**. The importer uses the configured Eventyay base URL, organizer slug, optional event slug, and API token to create or update WordPress content.

Imported data is stored and displayed in these places:

| Eventyay data | WordPress destination |
| ------------- | --------------------- |
| Event title, dates, times, timezone, location, URL, and description | `wpfa_event` posts and event post meta such as `wpfa_event_start_date`, `wpfa_event_start_time`, `wpfa_event_timezone`, `wpfa_event_location`, and `_wpfa_eventyay_event_slug` |
| Speakers | `wpfa_speaker` posts linked to the imported event through `wpfa_event_speakers` and `wpfa_speaker_events` |
| Event-specific speaker dashboard data | `wp-content/uploads/fossasia-data/speakers-{event_id}.json` |
| Event schedule rows | `wp-content/uploads/fossasia-data/schedule-{event_id}.json` |
| About text, registration button, and visibility settings | `wp-content/uploads/fossasia-data/site-settings-{event_id}.json` |

On the frontend, imported data appears on the single event page and on the event-filtered speaker list, for example `/speakers/?event={event-slug}`. The default speakers archive does not mix all event speakers together; select an event to view that event's own speaker list.

## Calendar Export And Timezones

Single event pages expose an **Add to calendar** link when the event has a valid start date. The primary link opens a Google Calendar event template, and the fallback `.ics` download is available from `/wp-json/wpfaevent/v1/events/{event_id}/ics`.

Event timezone behavior is deterministic:

* Each event can save an explicit timezone. If it is empty, WPFAevent falls back to the WordPress site timezone.
* All-day events export date-only `DTSTART` and exclusive date-only `DTEND` values.
* Timed events are interpreted in the event timezone and exported as UTC `DTSTART`/`DTEND` values.
* Eventyay imports save the Eventyay `timezone` field when present and preserve normalized source datetime values for calendar rendering/export.

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
