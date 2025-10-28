# FOSSASIA Event Plugin

A WordPress plugin for managing and displaying event and speaker data for FOSSASIA using Custom Post Types.

## Features
- **Custom Post Types:** `Events` and `Speakers` for structured, easy-to-manage data.
- **Frontend Rendering:** A simple `[wpfa_speakers]` shortcode to display a styled grid of speakers on any page.
- **Admin Interface:** Cleanly integrated into the WordPress dashboard, with settings and import/export tools.
- **Developer Tooling:** WP-CLI commands to quickly seed sample data for testing.

## Getting Started

The plugin uses WordPress Custom Post Types (CPTs) to store data, which is the standard and most robust way to handle structured content.

1.  **Install and Activate** the plugin.
2.  You will see a new **"FOSSASIA Events"** menu item in your WordPress dashboard.

### Managing Content

*   **To Add an Event:** Go to **FOSSASIA Events -> Add New**. Fill in the title, description, and event-specific details like date and venue.
*   **To Add a Speaker:** Go to **FOSSASIA Events -> Speakers -> Add New**. Fill in the speaker's name, bio, and their associated role and organization.

### Displaying Speakers

To display the list of speakers on the front end of your site:

1.  Create a new Page or Post in WordPress.
2.  Add a "Shortcode" block.
3.  Enter the following shortcode: `[wpfa_speakers]`
4.  Publish the page. The speaker grid will be rendered automatically.

## For Developers: Seeding Data with WP-CLI

The plugin includes a WP-CLI command for developers to quickly seed test data.

This command creates 2 placeholder speakers and 1 event.
```bash
wp wpfa seed --minimal
```

The seeder command is **idempotent**, meaning it is safe to re-run. It will update existing posts based on their slugs instead of creating duplicates.