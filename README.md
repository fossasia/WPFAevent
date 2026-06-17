# WordPress FOSSASIA Event Plugin (WPFAevent)

The **FOSSASIA Event Plugin** provides WordPress integrations for [Eventyay](https://eventyay.com)-based events.
It lets you display event landing pages, speakers, events, past events, schedules, and code of conduct content using classic page templates, Gutenberg blocks, or shortcodes. Shortcodes and blocks use embedded template rendering so WPFA content can live inside existing theme pages without the standalone template header/footer.

This plugin is maintained by [FOSSASIA](https://fossasia.org) and is compatible with the **eventyay** platform.

## Features

- Display **speakers**, **sessions**, and **event schedules** from Eventyay or other compatible APIs.
- Works with the unified **Eventyay (Django + Vue 3)** architecture.
- Includes an **admin settings page** to configure JSON API endpoints and cache duration.
- Supports WPFA template **shortcodes** for embedding event pages anywhere on your site.
- Provides Gutenberg **blocks** for the landing, speakers, events, past events, schedule, and code of conduct templates.
- Uses embedded template output for shortcodes and blocks so templates work in classic and block themes.
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

4. Add WPFA template shortcodes to your pages or posts, for example:

   ```text
   [wpfaevent_landing]
   [wpfaevent_speakers]
   [wpfaevent_events]
   [wpfaevent_past_events]
   [wpfaevent_schedule]
   [wpfaevent_code_of_conduct]
   ```

   You can also add the matching WPFA blocks in the block editor.

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

## Event Attendee Information

Each `wpfa_event` post includes an **Attendee Information** meta box. Use the main editor for general venue and travel notes, then add extra information sections for focused topics such as accommodation options, accessibility notes, or attendee resources.

Extra sections are stored in the `wpfa_event_custom_tabs` post meta field and render on that event's single event page as separate sections. They are also added to the event section navigation with anchors such as `#custom-section-accommodation`.

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

## Calendar Export And Timezones

The schedule page exposes an **Add to calendar** link when an event has a valid start date. The link opens a Google Calendar event template. Individual events can also be downloaded as `.ics` files from `/wp-json/wpfaevent/v1/events/{event_id}/ics`.

Event timezone behavior is deterministic:

* Each event can save an explicit timezone. If it is empty, WPFAevent falls back to the WordPress site timezone.
* All-day events export date-only `DTSTART` and exclusive date-only `DTEND` values.
* Timed events are interpreted in the event timezone and exported as UTC `DTSTART`/`DTEND` values.

Run the calendar export checks with:

```bash
php tests/calendar-test.php
```

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
