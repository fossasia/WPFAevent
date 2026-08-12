=== WPFA Event ===
Contributors: fossasia
Tags: events, eventyay, schedule, speakers, calendar
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.1.0
License: Apache2
License URI: https://www.apache.org/licenses/LICENSE-2.0.txt

Eventyay event templates for WordPress pages, blocks, and shortcodes.

== Description ==
The FOSSASIA Event Plugin provides WordPress integrations for Eventyay-based events. It allows you to display WPFA landing, speakers, events, past events, schedule, and code of conduct templates directly on WordPress pages.

Template content can be added with Gutenberg blocks, classic page templates, or these shortcodes:

* [wpfaevent_landing]
* [wpfaevent_speakers]
* [wpfaevent_events]
* [wpfaevent_past_events]
* [wpfaevent_schedule]
* [wpfaevent_code_of_conduct]

The plugin also supports embedded template rendering for shortcodes and blocks, so WPFA content can appear inside existing classic or block theme layouts.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/wpfaevent` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the Eventyay API endpoint and cache settings under **Settings -> Event Plugin** in the WordPress admin dashboard.

== Frequently Asked Questions ==
= Where do I configure the Eventyay API settings? =
You can configure the Eventyay API endpoint, cache TTL, and sync intervals by navigating to **Settings -> Event Plugin** in your WordPress dashboard.

= How do I display events or speakers on a page? =
You can use the built-in Gutenberg blocks or use the shortcodes such as `[wpfaevent_events]` or `[wpfaevent_speakers]` on any page or post.

== Changelog ==
= 0.1.0 =
* Initial skeleton.
