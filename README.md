# FOSSASIA Event WordPress Plugin

A plugin to create and manage event landing pages, speakers, schedules, and sponsors within WordPress using Custom Post Types.

## Features
- Creates `Event` and `Speaker` Custom Post Types (CPTs).
- Provides a clean, database-driven way to manage event data.
- (For Developers) A WP-CLI command to seed test data for development and testing.

## Getting Started

1.  **Install and Activate** the plugin.
2.  You will see new "Events" and "Speakers" menu items in your WordPress admin sidebar.
3.  You can now create and manage events and speakers just like standard WordPress posts.
4.  To display your content, you will use shortcodes (e.g., `[wpfa_speakers]`) or custom templates that query these new post types.


## For Developers: Seeding Data with WP-CLI

The plugin includes a WP-CLI command for developers to quickly seed test data. This is useful for setting up a development environment, especially when working with the Custom Post Type architecture.

**Prerequisites:**
- WP-CLI must be installed.
- The plugin must be activated.

**Commands:**

1.  **Seed Minimal Data:**
    This command creates 2 placeholder speakers and 1 event in the database.
    ```bash
    wp wpfa seed --minimal
    ```

2.  **Seed from JSON file:**
    This command populates CPT data from a specified JSON file. A sample fixture is included in the plugin.
    ```bash
    wp wpfa seed --from-json=wp-content/plugins/wpfa-event/assets/demo/minimal.json
    ```

The seeder command is **idempotent**, meaning it is safe to re-run. It will update existing posts based on their slugs instead of creating duplicates.