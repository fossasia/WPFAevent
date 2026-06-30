<?php
/**
 * Registers and loads plugin-provided page templates.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
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
 * - Registering shortcode, block, and pattern entry points for block themes
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Templates {

	/**
	 * List of plugin-provided page templates.
	 *
	 * Keys are stable template identifiers, values describe each entry point.
	 *
	 * @since 1.0.0
	 * @var   array<string, array<string, string>>
	 */
	private static $templates = array(
		'landing'         => array(
			'file'      => 'page-landing.php',
			'label'     => 'WPFA - Landing',
			'shortcode' => 'wpfaevent_landing',
			'block'     => 'landing',
			'title'     => 'WPFA Landing',
		),
		'speakers'        => array(
			'file'      => 'page-speakers.php',
			'label'     => 'WPFA - Speakers',
			'shortcode' => 'wpfaevent_speakers',
			'block'     => 'speakers',
			'title'     => 'WPFA Speakers',
		),
		'events'          => array(
			'file'      => 'page-events.php',
			'label'     => 'WPFA - Events',
			'shortcode' => 'wpfaevent_events',
			'block'     => 'events',
			'title'     => 'WPFA Events',
		),
		'past_events'     => array(
			'file'      => 'page-past-events.php',
			'label'     => 'WPFA - Past Events',
			'shortcode' => 'wpfaevent_past_events',
			'block'     => 'past-events',
			'title'     => 'WPFA Past Events',
		),
		'schedule'        => array(
			'file'      => 'page-schedule.php',
			'label'     => 'WPFA - Schedule',
			'shortcode' => 'wpfaevent_schedule',
			'block'     => 'schedule',
			'title'     => 'WPFA Schedule',
		),
		'code_of_conduct' => array(
			'file'      => 'page-code-of-conduct.php',
			'label'     => 'WPFA - Code of Conduct',
			'shortcode' => 'wpfaevent_code_of_conduct',
			'block'     => 'code-of-conduct',
			'title'     => 'WPFA Code of Conduct',
		),
		'admin_dashboard' => array(
			'file'      => 'admin-dashboard.php',
			'label'     => 'WPFA - Admin Dashboard',
			'shortcode' => 'wpfaevent_admin_dashboard',
			'block'     => 'admin-dashboard',
			'title'     => 'WPFA Admin Dashboard',
		),
	);

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
		add_filter( 'theme_page_templates', array( __CLASS__, 'register' ) );
		add_filter( 'single_template', array( __CLASS__, 'load' ), 99 );
		add_filter( 'template_include', array( __CLASS__, 'load' ), 99 );
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
		add_action( 'init', array( __CLASS__, 'register_patterns' ) );
	}

	/**
	 * Returns localized template labels keyed by template filename.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	private static function get_localized_template_labels() {
		$labels = array();

		foreach ( self::$templates as $key => $template ) {
			$labels[ $template['file'] ] = self::get_template_label( $key );
		}

		return $labels;
	}

	/**
	 * Registers plugin page templates with WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $templates Existing theme templates.
	 * @return array<string, string> Modified templates array including plugin templates.
	 */
	public static function register( $templates ) {
		foreach ( self::get_localized_template_labels() as $file => $label ) {
			$templates[ $file ] = $label;
		}

		return $templates;
	}

	/**
	 * Loads the appropriate plugin template when selected on a page.
	 *
	 * Ensures:
	 * - Only affects singular pages
	 * - Template file exists before overriding WordPress resolution
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Absolute path to the resolved template.
	 * @return string Absolute path to the template to load.
	 */
	public static function load( $template ) {
		if ( is_singular( 'wpfa_speaker' ) ) {
			$candidate = WPFAEVENT_PATH . 'public/templates/single-wpfa-speaker.php';

			if ( file_exists( $candidate ) ) {
				return $candidate;
			}
		}

		if ( is_singular( 'wpfa_event' ) ) {
			$candidate = WPFAEVENT_PATH . 'public/templates/single-wpfa-event.php';

			if ( file_exists( $candidate ) ) {
				return $candidate;
			}
		}

		if ( is_post_type_archive( 'wpfa_speaker' ) ) {
			$candidate = WPFAEVENT_PATH . 'public/templates/page-speakers.php';

			if ( file_exists( $candidate ) ) {
				return $candidate;
			}
		}
		if ( is_post_type_archive( 'wpfa_event' ) ) {
			$candidate = WPFAEVENT_PATH . 'public/templates/page-events.php';

			if ( file_exists( $candidate ) ) {
				return $candidate;
			}
		}

		if ( is_singular( 'page' ) ) {
			$chosen = get_page_template_slug( get_queried_object_id() );
			$key    = self::get_template_key_by_file( $chosen );

			if ( $key ) {
				if ( 'admin_dashboard' === $key ) {
					$candidate = WPFAEVENT_PATH . 'admin/partials/' . self::$templates[ $key ]['file'];
				} else {
					$candidate = WPFAEVENT_PATH . 'public/templates/' . self::$templates[ $key ]['file'];
				}

				if ( file_exists( $candidate ) ) {
					return $candidate;
				}
			}
		}

		return $template;
	}

	/**
	 * Registers shortcode entry points for WPFA templates.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_shortcodes() {
		foreach ( self::$templates as $template ) {
			add_shortcode( $template['shortcode'], array( __CLASS__, 'render_shortcode' ) );
		}

		add_shortcode( 'wpfaevent_template', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Registers dynamic blocks for WPFA templates.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		self::register_block_assets();

		wp_register_script(
			'wpfaevent-blocks',
			WPFAEVENT_URL . 'public/js/wpfaevent-blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
			WPFAEVENT_VERSION,
			true
		);

		wp_localize_script(
			'wpfaevent-blocks',
			'wpfaeventBlocks',
			self::get_block_editor_data()
		);

		foreach ( self::$templates as $key => $template ) {
			register_block_type(
				'wpfaevent/' . $template['block'],
				array(
					'api_version'     => 2,
					'editor_script'   => 'wpfaevent-blocks',
					'style'           => self::get_block_style_handle( $key ),
					'supports'        => array(
						'align' => array( 'wide', 'full' ),
					),
					'attributes'      => array(
						'align' => array(
							'type'    => 'string',
							'default' => 'full',
						),
					),
					'render_callback' => array( __CLASS__, 'render_block' ),
				)
			);
		}
	}

	/**
	 * Registers block patterns for WPFA templates.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_patterns() {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category(
				'wpfaevent',
				array( 'label' => __( 'WPFA Event', 'wpfaevent' ) )
			);
		}

		foreach ( self::$templates as $key => $template ) {
			register_block_pattern(
				'wpfaevent/' . $template['block'],
				array(
					'title'      => self::get_template_title( $key ),
					'categories' => array( 'wpfaevent' ),
					'content'    => '<!-- wp:wpfaevent/' . $template['block'] . ' /-->',
				)
			);
		}
	}

	/**
	 * Renders WPFA shortcode output.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $atts    Shortcode attributes.
	 * @param string                $content Shortcode content.
	 * @param string                $tag     Shortcode tag.
	 * @return string Rendered shortcode markup.
	 */
	public static function render_shortcode( $atts = array(), $content = '', $tag = '' ) {
		$key = self::get_template_key_by_shortcode( $tag );

		if ( 'wpfaevent_template' === $tag ) {
			$atts = shortcode_atts(
				array(
					'template' => 'events',
					'align'    => '',
				),
				$atts,
				$tag
			);
			$key  = self::normalize_template_key( $atts['template'] );
		} else {
			$atts = shortcode_atts(
				array(
					'align' => '',
				),
				$atts,
				$tag
			);
		}

		$output = self::render_embed( $key );

		if ( '' === $output ) {
			return $output;
		}

		if ( ! empty( $atts['align'] ) && in_array( $atts['align'], array( 'wide', 'full' ), true ) ) {
			$block_slug = isset( self::$templates[ $key ]['block'] ) ? self::$templates[ $key ]['block'] : 'template';
			$class_name = 'wp-block-wpfaevent-' . esc_attr( $block_slug ) . ' align' . esc_attr( $atts['align'] );
			return '<div class="' . $class_name . '">' . $output . '</div>';
		}

		return $output;
	}

	/**
	 * Renders WPFA dynamic block output.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Block content.
	 * @param WP_Block             $block      Block instance.
	 * @return string Rendered block markup.
	 */
	public static function render_block( $attributes, $content, $block ) {
		$block_name = ( is_object( $block ) && isset( $block->name ) ) ? $block->name : '';
		$key        = self::get_template_key_by_block_name( $block_name );
		$output     = self::render_embed( $key );

		if ( '' === $output || ! function_exists( 'get_block_wrapper_attributes' ) ) {
			return $output;
		}

		return '<div ' . get_block_wrapper_attributes() . '>' . $output . '</div>';
	}

	/**
	 * Renders a template as embeddable content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Template key.
	 * @return string Rendered template markup.
	 */
	public static function render_embed( $key ) {
		$key = self::normalize_template_key( $key );

		if ( ! isset( self::$templates[ $key ] ) ) {
			return '';
		}

		$template_file = WPFAEVENT_PATH . 'public/templates/' . self::$templates[ $key ]['file'];
		if ( ! file_exists( $template_file ) ) {
			return '';
		}

		self::enqueue_template_assets( $key );

			$previous                            = isset( $GLOBALS['wpfaevent_template_embed'] ) ? $GLOBALS['wpfaevent_template_embed'] : null;
			$GLOBALS['wpfaevent_template_embed'] = true;

		ob_start();
		echo '<div class="wpfaevent wpfaevent-embed">';
		include $template_file;
		echo '</div>';
		$output = ob_get_clean();

		if ( null === $previous ) {
			unset( $GLOBALS['wpfaevent_template_embed'] );
		} else {
			$GLOBALS['wpfaevent_template_embed'] = $previous;
		}

		return $output;
	}

	/**
	 * Gets template keys that are active on the current request.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Active template keys.
	 */
	public static function get_active_template_keys() {
		$active = array();

		if ( is_post_type_archive( 'wpfa_event' ) ) {
			$active[] = 'events';
		}

		if ( is_post_type_archive( 'wpfa_speaker' ) ) {
			$active[] = 'speakers';
		}

		if ( is_singular( 'page' ) ) {
			$chosen = get_page_template_slug( get_queried_object_id() );
			$key    = self::get_template_key_by_file( $chosen );

			if ( $key ) {
				$active[] = $key;
			}
		}

		$post = get_post( get_queried_object_id() );
		if ( $post instanceof WP_Post ) {
			foreach ( self::$templates as $key => $template ) {
				if ( has_shortcode( $post->post_content, $template['shortcode'] ) || has_block( 'wpfaevent/' . $template['block'], $post ) ) {
					$active[] = $key;
				}
			}

			$active = array_merge( $active, self::get_generic_shortcode_template_keys( $post->post_content ) );
		}

		return array_values( array_unique( $active ) );
	}

	/**
	 * Checks if a template file is active on the current request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Template file name.
	 * @return bool True when active.
	 */
	public static function is_template_file_active( $file ) {
		$key = self::get_template_key_by_file( $file );

		return $key && in_array( $key, self::get_active_template_keys(), true );
	}

	/**
	 * Checks if a template uses pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Template key.
	 * @return bool True when the template uses pagination.
	 */
	public static function template_uses_pagination( $key ) {
		return in_array( $key, array( 'events', 'past_events', 'speakers', 'schedule' ), true );
	}

	/**
	 * Gets the template key for a template file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Template file name.
	 * @return string Template key, or empty string.
	 */
	private static function get_template_key_by_file( $file ) {
		foreach ( self::$templates as $key => $template ) {
			if ( $template['file'] === $file ) {
				return $key;
			}
		}

		return '';
	}

	/**
	 * Normalizes public template names to internal template keys.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Template key, slug, shortcode-style name, or file.
	 * @return string Template key, or empty string.
	 */
	private static function normalize_template_key( $key ) {
		$raw_key = (string) $key;
		$key     = sanitize_key( $key );

		if ( isset( self::$templates[ $key ] ) ) {
			return $key;
		}

		foreach ( self::$templates as $template_key => $template ) {
			if ( $template['block'] === $key || $template['file'] === $raw_key ) {
				return $template_key;
			}
		}

		return '';
	}

	/**
	 * Gets template keys from generic template shortcode instances.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Post content.
	 * @return array<int, string> Template keys used by generic shortcodes.
	 */
	private static function get_generic_shortcode_template_keys( $content ) {
		$active = array();

		if ( false === strpos( $content, '[wpfaevent_template' ) ) {
			return $active;
		}

		$pattern = get_shortcode_regex( array( 'wpfaevent_template' ) );
		if ( ! preg_match_all( '/' . $pattern . '/s', $content, $matches, PREG_SET_ORDER ) ) {
			return $active;
		}

		foreach ( $matches as $shortcode_match ) {
			$attributes = shortcode_parse_atts( $shortcode_match[3] );
			$attributes = shortcode_atts(
				array(
					'template' => 'events',
				),
				is_array( $attributes ) ? $attributes : array(),
				'wpfaevent_template'
			);
			$key        = self::normalize_template_key( $attributes['template'] );

			if ( $key ) {
				$active[] = $key;
			}
		}

		return $active;
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
		foreach ( self::$templates as $key => $template ) {
			if ( $template['shortcode'] === $shortcode ) {
				return $key;
			}
		}

		return '';
	}

	/**
	 * Gets the template key for a block name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $block_name Block name.
	 * @return string Template key, or empty string.
	 */
	private static function get_template_key_by_block_name( $block_name ) {
		foreach ( self::$templates as $key => $template ) {
			if ( 'wpfaevent/' . $template['block'] === $block_name ) {
				return $key;
			}
		}

		return '';
	}

	/**
	 * Gets block metadata for the editor script.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, string>> Block editor metadata.
	 */
	private static function get_block_editor_data() {
		$blocks = array();

		foreach ( self::$templates as $key => $template ) {
			$blocks[] = array(
				'name'  => 'wpfaevent/' . $template['block'],
				'title' => self::get_template_title( $key ),
			);
		}

		return $blocks;
	}

	/**
	 * Gets the translated template label.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Template key.
	 * @return string Translated template label.
	 */
	private static function get_template_label( $key ) {
		switch ( $key ) {
			case 'landing':
				return __( 'WPFA - Landing', 'wpfaevent' );
			case 'speakers':
				return __( 'WPFA - Speakers', 'wpfaevent' );
			case 'events':
				return __( 'WPFA - Events', 'wpfaevent' );
			case 'past_events':
				return __( 'WPFA - Past Events', 'wpfaevent' );
			case 'schedule':
				return __( 'WPFA - Schedule', 'wpfaevent' );
			case 'code_of_conduct':
				return __( 'WPFA - Code of Conduct', 'wpfaevent' );
			case 'admin_dashboard':
				return __( 'WPFA - Admin Dashboard', 'wpfaevent' );
			default:
				return '';
		}
	}

	/**
	 * Gets the translated template title.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Template key.
	 * @return string Translated template title.
	 */
	private static function get_template_title( $key ) {
		switch ( $key ) {
			case 'landing':
				return __( 'WPFA Landing', 'wpfaevent' );
			case 'speakers':
				return __( 'WPFA Speakers', 'wpfaevent' );
			case 'events':
				return __( 'WPFA Events', 'wpfaevent' );
			case 'past_events':
				return __( 'WPFA Past Events', 'wpfaevent' );
			case 'schedule':
				return __( 'WPFA Schedule', 'wpfaevent' );
			case 'code_of_conduct':
				return __( 'WPFA Code of Conduct', 'wpfaevent' );
			case 'admin_dashboard':
				return __( 'WPFA Admin Dashboard', 'wpfaevent' );
			default:
				return '';
		}
	}

	/**
	 * Registers frontend styles for block-template rendering.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function register_block_assets() {
		wp_register_style(
			'wpfaevent',
			WPFAEVENT_URL . 'public/css/wpfaevent-public.css',
			array(),
			WPFAEVENT_VERSION,
			'all'
		);

		wp_register_style(
			'wpfaevent-navigation',
			WPFAEVENT_URL . 'public/css/components/navigation.css',
			array( 'wpfaevent' ),
			WPFAEVENT_VERSION,
			'all'
		);

		wp_register_style(
			'wpfaevent-pagination',
			WPFAEVENT_URL . 'public/css/components/pagination.css',
			array( 'wpfaevent' ),
			WPFAEVENT_VERSION,
			'all'
		);

		wp_register_style(
			'wpfaevent-code-of-conduct',
			WPFAEVENT_URL . 'public/css/templates/code-of-conduct.css',
			array( 'wpfaevent', 'wpfaevent-navigation' ),
			WPFAEVENT_VERSION,
			'all'
		);

		wp_register_style(
			'wpfaevent-speakers',
			WPFAEVENT_URL . 'public/css/templates/speakers.css',
			array( 'wpfaevent', 'wpfaevent-navigation', 'wpfaevent-pagination' ),
			WPFAEVENT_VERSION,
			'all'
		);

		wp_register_style(
			'wpfaevent-past-events',
			WPFAEVENT_URL . 'public/css/templates/past-events.css',
			array( 'wpfaevent', 'wpfaevent-navigation', 'wpfaevent-pagination' ),
			WPFAEVENT_VERSION,
			'all'
		);

		wp_register_style(
			'wpfaevent-events',
			WPFAEVENT_URL . 'public/css/templates/events.css',
			array( 'wpfaevent', 'wpfaevent-pagination' ),
			WPFAEVENT_VERSION,
			'all'
		);

		wp_register_style(
			'wpfaevent-schedule',
			WPFAEVENT_URL . 'public/css/templates/schedule.css',
			array( 'wpfaevent', 'wpfaevent-event' ),
			WPFAEVENT_VERSION,
			'all'
		);
	}

	/**
	 * Gets the frontend style handle for a block.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Template key.
	 * @return string Style handle.
	 */
	private static function get_block_style_handle( $key ) {
		$handles = array(
			'events'          => 'wpfaevent-events',
			'schedule'        => 'wpfaevent-schedule',
			'speakers'        => 'wpfaevent-speakers',
			'past_events'     => 'wpfaevent-past-events',
			'code_of_conduct' => 'wpfaevent-code-of-conduct',
		);

		return isset( $handles[ $key ] ) ? $handles[ $key ] : 'wpfaevent';
	}

	/**
	 * Enqueues template assets when content is rendered directly.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Template key.
	 * @return void
	 */
	private static function enqueue_template_assets( $key ) {
		if ( ! wp_style_is( 'wpfaevent', 'enqueued' ) ) {
			wp_enqueue_style( 'wpfaevent' );
		}

		if ( in_array( $key, array( 'speakers', 'past_events', 'code_of_conduct' ), true ) && ! wp_style_is( 'wpfaevent-navigation', 'enqueued' ) ) {
			wp_enqueue_style( 'wpfaevent-navigation' );
		}

		if ( self::template_uses_pagination( $key ) && ! wp_style_is( 'wpfaevent-pagination', 'enqueued' ) ) {
			wp_enqueue_style( 'wpfaevent-pagination' );
		}

		if ( 'speakers' === $key ) {
			wp_enqueue_style( 'wpfaevent-speakers' );
			wp_enqueue_script( 'wpfaevent-speakers' );
		}

		if ( 'past_events' === $key ) {
			wp_enqueue_style( 'wpfaevent-past-events' );
		}

		if ( 'events' === $key ) {
			wp_enqueue_style( 'wpfaevent-events' );
		}

		if ( 'schedule' === $key ) {
			wp_enqueue_style( 'wpfaevent-event' );
			wp_enqueue_style( 'wpfaevent-schedule' );
		}

		if ( 'code_of_conduct' === $key ) {
			wp_enqueue_style( 'wpfaevent-code-of-conduct' );
		}
	}
}

if ( ! class_exists( 'WPFA_Templates' ) ) {
	class_alias( 'Wpfaevent_Templates', 'WPFA_Templates' );
}
