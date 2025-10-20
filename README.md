# WPFA Event Plugin

This plugin provides a basic scaffold for the FOSSASIA event listings functionality, built following the WordPress Plugin Boilerplate structure.

## Structure

The plugin includes a standard boilerplate structure:
- Main plugin file (`wpfaevent.php`) for activation/deactivation and initialization.
- A core orchestrator class (`includes/class-wpfaevent.php`).
- A loader class (`includes/class-wpfaevent-loader.php`) to manage WordPress hooks.
- Separate classes and directories for admin-specific (`/admin`) and public-facing (`/public`) functionality.
- Internationalization support (`includes/class-wpfaevent-i18n.php`).