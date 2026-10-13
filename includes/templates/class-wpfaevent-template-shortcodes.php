<?php
/**
 * Registers and renders shortcode entry points for WPFA templates.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders shortcode entry points for WPFA templates.
 *
 * Each plugin template gets its own shortcode (for example `[wpfaevent_events]`),
 * plus the generic `[wpfaevent_template template="..."]` shortcode. Template
 * definitions and embedded rendering stay in Wpfaevent_Templates.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/templates
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Template_Shortcodes {

	/**
	 * Generic shortcode tag that accepts a `template` attribute.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	const GENERIC_TAG = 'wpfaevent_template';

	/**
	 * Registers one shortcode per template plus the generic template shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register() {
		foreach ( Wpfaevent_Templates::get_templates() as $template ) {
			add_shortcode( $template['shortcode'], array( __CLASS__, 'render' ) );
		}

		add_shortcode( self::GENERIC_TAG, array( __CLASS__, 'render' ) );
	}

	/**
	 * Renders WPFA shortcode output.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string>|string $atts    Shortcode attributes.
	 * @param string                       $content Shortcode content.
	 * @param string                       $tag     Shortcode tag.
	 * @return string Rendered shortcode markup.
	 */
	public static function render( $atts = array(), $content = '', $tag = '' ) {
		$templates = Wpfaevent_Templates::get_templates();
		$key       = self::get_template_key_by_shortcode( $tag );

		if ( self::GENERIC_TAG === $tag ) {
			$atts = shortcode_atts(
				array(
					'template' => 'events',
					'align'    => '',
				),
				$atts,
				$tag
			);
			$key  = Wpfaevent_Templates::normalize_template_key( $atts['template'] );
		} else {
			$atts = shortcode_atts(
				array(
					'align' => '',
				),
				$atts,
				$tag
			);
		}

		$output = Wpfaevent_Templates::render_embed( $key );

		if ( '' === $output ) {
			return $output;
		}

		if ( ! empty( $atts['align'] ) && in_array( $atts['align'], array( 'wide', 'full' ), true ) ) {
			$block_slug = isset( $templates[ $key ]['block'] ) ? $templates[ $key ]['block'] : 'template';
			$class_name = 'wp-block-wpfaevent-' . esc_attr( $block_slug ) . ' align' . esc_attr( $atts['align'] );
			return '<div class="' . $class_name . '">' . $output . '</div>';
		}

		return $output;
	}

	/**
	 * Gets the template key for a shortcode tag.
	 *
	 * @since 1.0.0
	 *
	 * @param string $shortcode Shortcode tag.
	 * @return string Template key, or empty string.
	 */
	private static function get_template_key_by_shortcode( $shortcode ) {
		foreach ( Wpfaevent_Templates::get_templates() as $key => $template ) {
			if ( $template['shortcode'] === $shortcode ) {
				return $key;
			}
		}

		return '';
	}
}
