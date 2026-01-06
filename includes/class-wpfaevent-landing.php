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
	public function register_template( $templates ) {
		$templates['public/partials/wpfaevent-landing-template.php'] = 'FOSSASIA Summit Landing Page (Plugin)';
		$templates['public/partials/wpfaevent-landing-template.php'] = 'FOSSASIA Events Listing (Plugin)';
		$templates['public/partials/wpfaevent-landing-template.php'] = 'FOSSASIA Admin Dashboard (Plugin)';
		$templates['public/partials/speakers-page.php']              = 'FOSSASIA Speakers Page (Plugin)';
		$templates['public/partials/schedule-page.php']              = 'FOSSASIA Schedule Page (Plugin)';
		$templates['public/partials/past-events-page.php']           = 'FOSSASIA Past Events (Plugin)';
		$templates['public/partials/wpfaevent-landing-template.php'] = 'FOSSASIA Code of Conduct (Plugin)';
		return $templates;
	}

	/**
	 * Loads the custom template file when a page with that template is viewed.
	 *
	 * @param string $template The path of the template to include.
	 * @return string The path of the template file.
	 */
	public function load_template( $template ) {
		if ( is_page_template( 'public/partials/wpfaevent-landing-template.php' ) ) {
			return plugin_dir_path( __DIR__ ) . 'public/partials/wpfaevent-landing-template.php';
		}
		if ( is_page_template( 'templates/events-listing-page.php' ) ) {
			return plugin_dir_path( __DIR__ ) . 'public/partials/wpfaevent-landing-template.php';
		}
		if ( is_page_template( 'admin/partials/admin-dashboard.php' ) ) {
			return plugin_dir_path( __DIR__ ) . 'admin/partials/admin-dashboard.php';
		}
		if ( is_page_template( 'public/partials/speakers-page.php' ) ) {
			return plugin_dir_path( __DIR__ ) . 'public/partials/speakers-page.php';
		}
		if ( is_page_template( 'public/partials/schedule-page.php' ) ) {
			return plugin_dir_path( __DIR__ ) . 'public/partials/schedule-page.php';
		}
		if ( is_page_template( 'public/partials/past-events-page.php' ) ) {
			return plugin_dir_path( __DIR__ ) . 'public/partials/past-events-page.php';
		}
		if ( is_page_template( 'templates/code-of-conduct-page.php' ) ) {
			return plugin_dir_path( __DIR__ ) . 'public/partials/wpfaevent-landing-template.php';
		}
		return $template;
	}

	/**
	 * Shortcode renderer for landing page.
	 *
	 * @return string HTML output of the landing page.
	 */
	public function render_landing() {
		ob_start();
		?>
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

		<div class="wpfaevent-landing-container">
			<h2><?php echo esc_html__( 'FOSSASIA Events', 'wpfaevent' ); ?></h2>
			<p>
				<?php
				printf(
					esc_html__( 'For documentation and setup instructions, visit the %s.', 'wpfaevent' ),
					sprintf(
						'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
						esc_url( 'https://github.com/fossasia/WPFAevent' ),
						esc_html__( 'official repository', 'wpfaevent' )
					)
				);
				?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Sets up the necessary pages on plugin activation.
	 */
	public function setup_pages() {
		$this->create_page_if_not_exists( 'FOSSASIA Summit', 'fossasia-summit', 'public/partials/wpfaevent-landing-template.php' );
		$this->create_page_if_not_exists( 'Speakers', 'speakers', 'public/partials/speakers-page.php' );
		$this->create_page_if_not_exists( 'Full Schedule', 'full-schedule', 'public/partials/schedule-page.php' );
		$this->create_page_if_not_exists( 'Admin Dashboard', 'admin-dashboard', 'public/partials/wpfaevent-landing-template.php', 'private' );
		$this->create_page_if_not_exists( 'Events', 'events', 'public/partials/wpfaevent-landing-template.php' );
		$this->create_page_if_not_exists( 'Past Events', 'past-events', 'public/partials/past-events-page.php' );
		$this->create_page_if_not_exists( 'Code of Conduct', 'code-of-conduct', 'public/partials/wpfaevent-landing-template.php' );
	}
}
