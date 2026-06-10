# AGENTS.md

Guidance for coding agents working on WPFAevent.

## Project Context

WPFAevent is a WordPress plugin for FOSSASIA/Eventyay events. It defines custom post types for events and speakers, imports Eventyay data, and renders event and speaker templates on the frontend.

## Local Workflow

- Work from the plugin root: `wp-content/plugins/event-plugin`.
- Prefer `rg` for searching code.
- Keep edits scoped to the requested feature or review comment.
- Do not revert unrelated local changes. The worktree may contain user changes.
- Use WordPress APIs for filesystem, HTTP, post meta, escaping, sanitization, and nonce handling.
- Keep user-facing strings translation-ready with the `wpfaevent` text domain.

## Eventyay Import Notes

- Eventyay import settings and import handling live in `admin/class-wpfaevent-admin.php`.
- The plugin Settings menu must remain available as `WPFAEvent -> Settings`.
- The Eventyay import page lives under `Events -> Import Events`.
- Paginated Eventyay `next` URLs must not leak the saved API token. Absolute `next` URLs are valid only when they share the configured Eventyay origin.
- Preserve timezone offsets in Eventyay date-time strings. Do not convert imported event/session times to UTC unless the user explicitly asks for UTC.
- Empty Eventyay imports must clear stale Eventyay-owned dashboard data while preserving manual dashboard state where supported.

## Data Storage

- Events are stored as `wpfa_event` posts.
- Speakers are stored as `wpfa_speaker` posts.
- Event-to-speaker links are stored in `wpfa_event_speakers`.
- Speaker-to-event links are stored in `wpfa_speaker_events`.
- Imported dashboard JSON is stored under `wp-content/uploads/fossasia-data/`, using event-specific files:
  - `speakers-{event_id}.json`
  - `schedule-{event_id}.json`
  - `site-settings-{event_id}.json`
- Do not mix every event's speakers into one visible speaker list. Speaker lists should be event-scoped.

## Roles And Capabilities

- Role and capability registration lives in `includes/class-wpfaevent-roles.php`.
- The plugin adds an **Event Organizer** role (`wpfa_event_organizer`) for event staff who should manage events, speakers, and Eventyay imports without full WordPress administrator access.
- Plugin-level capabilities:
  - `manage_wpfa_settings` — WPFAEvent settings screen
  - `import_eventyay_events` — Eventyay import settings, import/update actions, and per-event sync
- Event and speaker CPT capabilities (`publish_events`, `edit_events`, `publish_speakers`, etc.) are granted to administrators and Event Organizers on activation/upgrade.
- Frontend dashboard edit controls use `Wpfaevent_Roles::current_user_can_manage_dashboard()` (`edit_events` + `edit_speakers`).
- Site-wide footer branding remains limited to WordPress administrators (`manage_options`).

## Architecture

- CPT meta registration and metabox handling belong under `includes/meta/`.
- Avoid adding parallel Event or Speaker metabox implementations to `admin/class-wpfaevent-admin.php`.
- Frontend templates live under `public/templates/`.
- Shared speaker card markup lives under `public/partials/speakers/`.

## Checks

Run these before handing work back:

```bash
php -l admin/class-wpfaevent-admin.php
php -l includes/class-wpfaevent.php
php -l includes/class-wpfaevent-roles.php
php -l includes/meta/class-wpfaevent-meta-event.php
php -l includes/meta/class-wpfaevent-meta-speaker.php
vendor/bin/phpcs admin/class-wpfaevent-admin.php includes/class-wpfaevent.php includes/meta/class-wpfaevent-meta-event.php includes/meta/class-wpfaevent-meta-speaker.php public/templates/page-speakers.php
git diff --check
```

For import and relationship changes, also sanity-check with WP-CLI when available.
