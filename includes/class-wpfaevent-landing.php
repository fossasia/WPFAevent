<?php
/**
 * Handles the landing page logic for the WPFAEvent plugin.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Wpfaevent_Landing
 *
 * Provides the landing page logic as a shortcode or callable template.
 */
class Wpfaevent_Landing {

	/**
	 * Initialize hooks and shortcodes.
	 */
	public function init() {
		// Register shortcode for the landing page.
		add_shortcode( 'wpfaevent_landing', array( $this, 'render_landing' ) );
	}

	/**
	 * Render the landing page output.
	 *
	 * @return string HTML output of the landing page.
	 */
	public function render_landing() {
		ob_start();
		?>
		<!-- ===============================
			 WPFAEvent Landing Page Template
			 Adapted from wpfa-event-landing.php
			 =============================== -->

		<div class="wpfaevent-landing-container">
			<h2>Welcome to the FOSSASIA Event Plugin</h2>
			<p>
				This plugin integrates <strong>Eventyay</strong> data with your WordPress site.
				You can showcase sessions, speakers, and event schedules seamlessly.
			</p>

			<div class="wpfaevent-landing-content">
				<ul>
					<li>🎤 Display sessions and speakers dynamically</li>
					<li>📅 Show event schedules from Eventyay API</li>
					<li>🧩 Embed using shortcodes or custom templates</li>
					<li>🧭 Easy setup through the WordPress dashboard</li>
				</ul>
			</div>

			<p>
				For documentation and setup instructions, visit the
				<a href="https://github.com/fossasia/WPFAevent" target="_blank" rel="noopener noreferrer">
					official repository
				</a>.
			</p>
		</div>

		<style>
			.wpfaevent-landing-container {
				background: #f9f9f9;
				padding: 20px;
				border-radius: 10px;
				box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
				max-width: 800px;
				margin: 30px auto;
				font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
			}
			.wpfaevent-landing-container h2 {
				color: #e74c3c;
			}
			.wpfaevent-landing-content ul {
				list-style-type: none;
				padding-left: 0;
			}
			.wpfaevent-landing-content li {
				padding: 5px 0;
			}
			.wpfaevent-landing-container a {
				color: #0073aa;
				text-decoration: none;
			}
			.wpfaevent-landing-container a:hover {
				text-decoration: underline;
			}
		</style>
		<?php
		return ob_get_clean();
	}
}
