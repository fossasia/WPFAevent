<?php
/**
 * Prevent direct access to this file.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and loads plugin-provided page templates.
 *
 * This class is responsible for:
 * - Registering custom page templates so they appear in the Page Attributes dropdown
 * - Resolving and loading the correct template file when a page uses one of them
 * - Gracefully skipping block themes, which rely on block-based templates
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 * @author     FOSSASIA <contact@fossasia.org>
 */
class WPFA_Templates {

	/**
	 * List of plugin-provided page templates.
	 *
	 * Keys are template filenames, values are human-readable labels.
	 *
	 * @since 1.0.0
	 * @var   array<string, string>
	 */
	private static $templates = [
		'page-landing.php'         => 'WPFA - Landing',
		'page-speakers.php'        => 'WPFA - Speakers',
		'page-events.php'          => 'WPFA - Events',
		'page-past-events.php'     => 'WPFA - Past Events',
		'page-schedule.php'        => 'WPFA - Schedule',
		'page-code-of-conduct.php' => 'WPFA - Code of Conduct',
	];

	/**
	 * Registers WordPress hooks for template registration and loading.
	 *
	 * Hooks into:
	 * - `theme_page_templates` to expose plugin templates in the admin UI
	 * - `template_include` to load the selected template at runtime
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'theme_page_templates', [ __CLASS__, 'register' ] );
		add_filter( 'template_include', [ __CLASS__, 'load' ] );
	}

	/**
	 * Registers plugin page templates with WordPress.
	 *
	 * Skips registration for block themes, which rely exclusively
	 * on block-based template resolution.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $templates Existing theme templates.
	 * @return array<string, string> Modified templates array including plugin templates.
	 */
	public static function register( $templates ) {
		// Don't register templates for block themes - they use block-based templates only
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			return $templates;
		}

		foreach ( self::$templates as $file => $label ) {
			$templates[ $file ] = __( $label, 'wpfaevent' );
		}

		return $templates;
	}

	/**
	 * Loads the appropriate plugin template when selected on a page.
	 *
	 * Ensures:
	 * - Only runs for classic themes
	 * - Only affects singular pages
	 * - Template file exists before overriding WordPress resolution
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Absolute path to the resolved template.
	 * @return string Absolute path to the template to load.
	 */
	public static function load( $template ) {
		// Don't load templates for block themes.
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			return $template;
		}

		if ( is_singular( 'page' ) ) {
			$chosen = get_page_template_slug( get_queried_object_id() );

			if ( isset( self::$templates[ $chosen ] ) ) {
				$candidate = WPFAEVENT_PATH . 'public/templates/' . $chosen;

				if ( file_exists( $candidate ) ) {
					return $candidate;
				}
			}
		}

		return $template;
	}
}
