<?php
/**
 * Prevent direct access to this file.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers plugin-provided page templates and loads them.
 */
class WPFA_Templates {

	private static $templates = [
		'page-landing.php'         => 'WPFA - Landing',
		'page-speakers.php'        => 'WPFA - Speakers',
		'page-events.php'          => 'WPFA - Events',
		'page-past-events.php'     => 'WPFA - Past Events',
		'page-schedule.php'        => 'WPFA - Schedule',
		'page-code-of-conduct.php' => 'WPFA - Code of Conduct',
	];

	public static function init() {
		add_filter( 'theme_page_templates', [ __CLASS__, 'register' ] );
		add_filter( 'template_include', [ __CLASS__, 'load' ] );
	}

	public static function register( $templates ) {
		foreach ( self::$templates as $file => $label ) {
			$templates[ $file ] = __( $label, 'wpfaevent' );
		}
		return $templates;
	}

	public static function load( $template ) {
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
