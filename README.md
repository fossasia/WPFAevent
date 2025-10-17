# FOSSASIA Event WordPress Plugin

A plugin to create and manage event landing pages, speakers, schedules, and sponsors within WordPress.

## Features
- Create and manage multiple, distinct event pages.
- A dedicated Admin Dashboard for each event to manage speakers, sponsors, schedule, and theme settings.
- One-click sample event creation for quick demos.
- (For Developers) A WP-CLI command to seed test data for development and testing.

## Getting Started: User Workflow

This plugin is designed to be minimal on activation. It does **not** create any event pages by default, giving you a clean slate to start from.

Here’s how to get your first event up and running:

### Option 1: Create a Pre-filled Sample Event (Recommended)

This is the quickest way to see the plugin in action.

1.  **Install and Activate** the plugin.
2.  Navigate to the **Events** page on your site (usually at `yourwebsite.com/events`).
3.  Click the **"Add Sample Event"** button.
4.  The page will reload, and you will see a new event card titled "FOSSASIA Summit (Sample)". This event is fully populated with sample speakers, sponsors, and content.
5.  You can now view the sample event page or click **"Edit Content"** on its card to explore the Admin Dashboard and see how it's configured.

### Option 2: Create a Custom Event from Scratch

Use this flow to build your own event from the ground up.

1.  **Install and Activate** the plugin.
2.  Navigate to the **Events** page (`yourwebsite.com/events`).
3.  Click the **"Create Custom Event"** button.
4.  Fill out the form with your event's details (name, date, location, etc.) and click **"Create Card"**.
5.  Your new, empty event will appear on the Events page.

#### Populating Your Custom Event

Your new event page is a blank canvas. To add content:

1.  On the Events page, find your new event's card and click **"Edit Content"**. This takes you to the Admin Dashboard for that specific event.
2.  To quickly see how the page will look, go to the **Data Sync** tab and click **"Import Sample Data"**. This will populate your event with placeholder speakers and sponsors, which you can then edit.
3.  Alternatively, use the other tabs (`Manage Speakers`, `Manage Sponsors`, etc.) to add your own content manually from scratch.

## For Developers: Seeding Data with WP-CLI

The plugin includes a WP-CLI command for developers to quickly seed test data. This is useful for setting up a development environment, especially when working with the Custom Post Type architecture.

**Prerequisites:**
- WP-CLI must be installed.
- The plugin must be activated.
- The `wpfa_event` and `wpfa_speaker` Custom Post Types must be registered and active for the command to work as intended.

**Commands:**

1.  **Seed Minimal Data:**
    This command creates 2 placeholder speakers and 1 event and links them together.
    ```bash
    wp wpfa seed --minimal
    ```

2.  **Seed from JSON file:**
    This command populates data from a specified JSON file. A sample fixture is included in the plugin.
    ```bash
    wp wpfa seed --from-json=wp-content/plugins/wpfa-event/assets/demo/minimal.json
    ```

The seeder command is **idempotent**, meaning it is safe to re-run. It will update existing posts based on their slugs instead of creating duplicates.