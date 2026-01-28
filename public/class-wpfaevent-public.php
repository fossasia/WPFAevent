<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Check if current page is using a WPFA template.
	 *
	 * @since    1.0.0
	 * @return   bool    True if WPFA template is active.
	 */
	private function is_wpfa_template() {
		$wpfa_templates = array(
			'page-code-of-conduct.php',
			'page-events.php',
			'page-past-events.php',
			'page-schedule.php',
			'page-speakers.php',
			'page-landing.php',
		);

		foreach ( $wpfa_templates as $template ) {
			if ( is_page_template( $template ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if current page uses pagination.
	 *
	 * @since    1.0.0
	 * @return   bool    True if template uses pagination.
	 */
	private function is_paginated_template() {
		$paginated_templates = array(
			'page-events.php',
			'page-past-events.php',
			'page-speakers.php',
			'page-schedule.php',
		);

		foreach ( $paginated_templates as $template ) {
			if ( is_page_template( $template ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get WPFA theme colors with filters applied.
	 *
	 * @since    1.0.0
	 * @return   array    Associative array of color values.
	 */
	private function get_theme_colors() {
		$colors = array(
			'brand'      => get_option( 'wpfa_brand_color', '#D51007' ),
			'background' => get_option( 'wpfa_background_color', '#f8f9fa' ),
			'text'       => get_option( 'wpfa_text_color', '#0b0b0b' ),
		);

		// Allow filtering
		$colors['brand']      = apply_filters( 'wpfa_brand_color', $colors['brand'] );
		$colors['background'] = apply_filters( 'wpfa_background_color', $colors['background'] );
		$colors['text']       = apply_filters( 'wpfa_text_color', $colors['text'] );

		return $colors;
	}

	/**
	 * Generate CSS custom properties for theme colors.
	 *
	 * @since    1.0.0
	 * @return   string    CSS rule with custom properties.
	 */
	private function generate_color_css() {
		$colors = $this->get_theme_colors();

		return sprintf(
			'.wpfaevent { --brand: %s; --bg: %s; --text: %s; }',
			esc_attr( $colors['brand'] ),
			esc_attr( $colors['background'] ),
			esc_attr( $colors['text'] )
		);
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpfaevent_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpfaevent_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// Base public styles (global, shared).
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/wpfaevent-public.css',
			array(),
			$this->version,
			'all'
		);

		// Only load component/template styles when WPFA template is active
		if ( ! $this->is_wpfa_template() ) {
			return;
		}

		// Navigation component (shared across templates)
		wp_enqueue_style(
			$this->plugin_name . '-navigation',
			plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/components/navigation.css',
			array( $this->plugin_name ),
			$this->version,
			'all'
		);

		// Pagination component (only templates with pagination)
		if ( $this->is_paginated_template() ) {
			wp_enqueue_style(
				$this->plugin_name . '-pagination',
				plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/components/pagination.css',
				array( $this->plugin_name ),
				$this->version,
				'all'
			);
		}

		// Add dynamic CSS variables
		wp_add_inline_style( $this->plugin_name, $this->generate_color_css() );

		// Template-specific styles.
		if ( is_page_template( 'page-code-of-conduct.php' ) ) {
			wp_enqueue_style(
				$this->plugin_name . '-code-of-conduct',
				plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/templates/code-of-conduct.css',
				array(
					$this->plugin_name,
					$this->plugin_name . '-navigation',
				),
				$this->version,
				'all'
			);
		}

		if ( is_page_template( 'page-speakers.php' ) ) {
			wp_enqueue_style(
				$this->plugin_name . '-speakers',
				plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/templates/speakers.css',
				array(
					$this->plugin_name,
					$this->plugin_name . '-navigation',
					$this->plugin_name . '-pagination',
				),
				$this->version,
				'all'
			);

			// Enqueue speakers JavaScript
			wp_enqueue_script(
				$this->plugin_name . '-speakers',
				plugin_dir_url( __FILE__ ) . 'js/wpfaevent-speakers.js',
				array( 'jquery' ),
				$this->version,
				true
			);

			// Pass data from PHP to JavaScript
			wp_localize_script(
				$this->plugin_name . '-speakers',
				'wpfaeventSpeakersConfig',      // JavaScript object name
				array(
					'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
					'adminNonce' => wp_create_nonce( 'wpfa_speakers_ajax' ),
					'isAdmin'    => current_user_can( 'manage_options' ),

					// All translatable strings
					'i18n'       => array(
						'confirmDelete'      => __( 'Are you sure you want to delete "%s"? This action cannot be undone.', 'wpfaevent' ),
						'deleteSuccess'      => __( 'Speaker deleted successfully. The page will now reload.', 'wpfaevent' ),
						'deleteError'        => __( 'Error deleting speaker', 'wpfaevent' ),
						'deleteErrorGeneric' => __( 'Error deleting speaker. Please try again.', 'wpfaevent' ),
						'addSuccess'         => __( 'Speaker added successfully. The page will now reload.', 'wpfaevent' ),
						'addError'           => __( 'Error adding speaker', 'wpfaevent' ),
						'addErrorGeneric'    => __( 'Error adding speaker. Please try again.', 'wpfaevent' ),
						'updateSuccess'      => __( 'Speaker updated successfully. The page will now reload.', 'wpfaevent' ),
						'updateError'        => __( 'Error updating speaker', 'wpfaevent' ),
						'updateErrorGeneric' => __( 'Error updating speaker. Please try again.', 'wpfaevent' ),
						'loadError'          => __( 'Error loading speaker data', 'wpfaevent' ),
						'fetchError'         => __( 'Error fetching speaker data', 'wpfaevent' ),
						'fetchErrorGeneric'  => __( 'Error fetching speaker data. Please try again.', 'wpfaevent' ),
						'noPermission'       => __( 'You do not have permission to perform this action.', 'wpfaevent' ),
						'resultsCount'       => __( 'Showing %d speakers', 'wpfaevent' ),
					),
				)
			);
		}

		/**
		* ---------------------------------------------------------------------
		* Template-specific styles (extension pattern)
		* ---------------------------------------------------------------------
		*
		* When adding CSS for a new plugin-provided page template:
		*
		* 1. Create a template-specific stylesheet under:
		*    public/css/templates/{template-name}.css
		*
		* 2. Conditionally enqueue it using is_page_template()
		*    to avoid loading unnecessary CSS on other pages.
		*
		* Example:
		*
		* if ( is_page_template( 'page-speakers.php' ) ) {
		*     wp_enqueue_style(
		*         $this->plugin_name . '-speakers',
		*         plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/templates/speakers.css',
		*         array(
		*             $this->plugin_name,
		*             $this->plugin_name . '-navigation',
		*         ),
		*         $this->version,
		*         'all'
		*     );
		* }
		*
		* This keeps base styles global, component styles reusable,
		* and template styles scoped to their respective pages.
		*/
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpfaevent_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpfaevent_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpfaevent-public.js', array( 'jquery' ), $this->version, false );
	}
}
