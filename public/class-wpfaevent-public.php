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
		if ( is_singular( array( 'wpfa_event', 'wpfa_speaker' ) ) ) {
			return true;
		}

		if ( is_post_type_archive( array( 'wpfa_event', 'wpfa_speaker' ) ) ) {
			return true;
		}

		if ( class_exists( 'Wpfaevent_Templates' ) ) {
			return ! empty( Wpfaevent_Templates::get_active_template_keys() );
		}

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
		if ( is_post_type_archive( array( 'wpfa_event', 'wpfa_speaker' ) ) ) {
			return true;
		}

		if ( class_exists( 'Wpfaevent_Templates' ) ) {
			foreach ( Wpfaevent_Templates::get_active_template_keys() as $key ) {
				if ( Wpfaevent_Templates::template_uses_pagination( $key ) ) {
					return true;
				}
			}

			return false;
		}

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
	 * Check if a WPFA template file is active on the current page.
	 *
	 * @since    1.0.0
	 * @param    string $template Template file name.
	 * @return   bool             True if the template is active.
	 */
	private function is_wpfa_template_file_active( $template ) {
		if ( class_exists( 'Wpfaevent_Templates' ) ) {
			return Wpfaevent_Templates::is_template_file_active( $template );
		}

		return is_page_template( $template );
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

		// Allow filtering.
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
	}

	/**
	 * Get localized Events template script data.
	 *
	 * @since    1.0.0
	 * @return   array<string, mixed> Script data for the Events template.
	 */
	private function get_events_script_data() {
		return array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'adminNonce' => wp_create_nonce( 'wpfa_events_ajax' ),
			'isAdmin'    => current_user_can( 'manage_options' ),
			'i18n'       => array(
				'addEventTitle'      => __( 'Create a New Event', 'wpfaevent' ),
				'editEventTitle'     => __( 'Edit Event', 'wpfaevent' ),
				'addEventButton'     => __( 'Create Card', 'wpfaevent' ),
				'editEventButton'    => __( 'Save Changes', 'wpfaevent' ),
				'creating'           => __( 'Creating...', 'wpfaevent' ),
				'saving'             => __( 'Saving...', 'wpfaevent' ),
				'loading'            => __( 'Loading...', 'wpfaevent' ),
				/* translators: %s: The name of the event being deleted. */
				'confirmDelete'      => __( 'Are you sure you want to delete "%s"? This action cannot be undone.', 'wpfaevent' ),
				'deleteSuccess'      => __( 'Event deleted successfully. The page will now reload.', 'wpfaevent' ),
				'deleteError'        => __( 'Error deleting event', 'wpfaevent' ),
				'deleteErrorGeneric' => __( 'Error deleting event. Please try again.', 'wpfaevent' ),
				'addSuccess'         => __( 'Event created successfully. The page will now reload.', 'wpfaevent' ),
				'addError'           => __( 'Error creating event', 'wpfaevent' ),
				'addErrorGeneric'    => __( 'Error creating event. Please try again.', 'wpfaevent' ),
				'updateSuccess'      => __( 'Event updated successfully. The page will now reload.', 'wpfaevent' ),
				'updateError'        => __( 'Error updating event', 'wpfaevent' ),
				'updateErrorGeneric' => __( 'Error updating event. Please try again.', 'wpfaevent' ),
				'noPermission'       => __( 'You do not have permission to perform this action.', 'wpfaevent' ),
				'loadError'          => __( 'Error loading event data', 'wpfaevent' ),
			),
		);
	}

	/**
	 * Register public assets so templates, shortcodes, and blocks can enqueue them.
	 *
	 * @since    1.0.0
	 */
	private function register_assets() {
		wp_register_style(
			$this->plugin_name,
			WPFAEVENT_URL . 'public/css/wpfaevent-public.css',
			array(),
			$this->version,
			'all'
		);

		wp_register_style(
			$this->plugin_name . '-navigation',
			WPFAEVENT_URL . 'public/css/components/navigation.css',
			array( $this->plugin_name ),
			$this->version,
			'all'
		);

		wp_register_style(
			$this->plugin_name . '-pagination',
			WPFAEVENT_URL . 'public/css/components/pagination.css',
			array( $this->plugin_name ),
			$this->version,
			'all'
		);

		wp_register_style(
			$this->plugin_name . '-code-of-conduct',
			WPFAEVENT_URL . 'public/css/templates/code-of-conduct.css',
			array(
				$this->plugin_name,
				$this->plugin_name . '-navigation',
			),
			$this->version,
			'all'
		);

		wp_register_style(
			$this->plugin_name . '-speakers',
			WPFAEVENT_URL . 'public/css/templates/speakers.css',
			array(
				$this->plugin_name,
				$this->plugin_name . '-navigation',
				$this->plugin_name . '-pagination',
			),
			$this->version,
			'all'
		);

		wp_register_style(
			$this->plugin_name . '-past-events',
			WPFAEVENT_URL . 'public/css/templates/past-events.css',
			array(
				$this->plugin_name,
				$this->plugin_name . '-navigation',
				$this->plugin_name . '-pagination',
			),
			$this->version,
			'all'
		);

		wp_register_style(
			$this->plugin_name . '-events',
			WPFAEVENT_URL . 'public/css/templates/events.css',
			array(
				$this->plugin_name,
				$this->plugin_name . '-pagination',
			),
			$this->version,
			'all'
		);

		wp_register_script(
			$this->plugin_name . '-speakers',
			plugin_dir_url( __FILE__ ) . 'js/wpfaevent-speakers.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name . '-speakers',
			'wpfaeventSpeakersConfig',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'adminNonce' => wp_create_nonce( 'wpfa_speakers_ajax' ),
				'isAdmin'    => current_user_can( 'manage_options' ),
				'i18n'       => array(
					/* translators: %s: speaker name. */
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
					/* translators: %d: number of speakers shown. */
					'resultsCount'       => __( 'Showing %d speakers', 'wpfaevent' ),
				),
			)
		);

		wp_register_script(
			$this->plugin_name . '-events',
			plugin_dir_url( __FILE__ ) . 'js/wpfaevent-events.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name . '-events',
			'wpfaeventEventsConfig',
			$this->get_events_script_data()
		);
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

		$this->register_assets();

		// Base public styles (global, shared).
		wp_enqueue_style( $this->plugin_name );

		// Add dynamic CSS variables.
		wp_add_inline_style( $this->plugin_name, $this->generate_color_css() );

		// Only load component/template styles when WPFA template is active.
		if ( ! $this->is_wpfa_template() ) {
			return;
		}

		// Navigation component (shared across templates).
		wp_enqueue_style( $this->plugin_name . '-navigation' );

		// Footer script (handles footer text updates, shared with events config).
		wp_enqueue_script(
			$this->plugin_name . '-footer',
			plugin_dir_url( __FILE__ ) . 'js/wpfaevent-footer.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Localize footer script data (shared with events config).
		wp_localize_script(
			$this->plugin_name . '-footer',
			'wpfaeventFooterConfig',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'adminNonce' => wp_create_nonce( 'wpfa_events_ajax' ), // Same nonce as events.
				'isAdmin'    => current_user_can( 'manage_options' ),
				'i18n'       => array(
					'saving'            => __( 'Saving...', 'wpfaevent' ),
					'saveFooter'        => __( 'Save Footer', 'wpfaevent' ),
					'footerSaveSuccess' => __( 'Footer text updated successfully.', 'wpfaevent' ),
					'footerSaveError'   => __( 'Error updating footer text.', 'wpfaevent' ),
					'noPermission'      => __( 'You do not have permission to perform this action.', 'wpfaevent' ),
				),
			)
		);

		// Pagination component (only templates with pagination).
		if ( $this->is_paginated_template() ) {
			wp_enqueue_style( $this->plugin_name . '-pagination' );
		}

		// Template-specific styles.
		if ( $this->is_wpfa_template_file_active( 'page-code-of-conduct.php' ) ) {
			wp_enqueue_style( $this->plugin_name . '-code-of-conduct' );
		}

		if ( $this->is_wpfa_template_file_active( 'page-speakers.php' ) || is_post_type_archive( 'wpfa_speaker' ) ) {
			wp_enqueue_style( $this->plugin_name . '-speakers' );
			wp_enqueue_script( $this->plugin_name . '-speakers' );
		}

		if ( is_singular( 'wpfa_speaker' ) ) {
			wp_enqueue_style(
				$this->plugin_name . '-speakers',
				plugin_dir_url( __DIR__ ) . 'public/css/templates/speakers.css',
				array(
					$this->plugin_name,
					$this->plugin_name . '-navigation',
				),
				$this->version,
				'all'
			);
		}

		// Past Events template.
		if ( $this->is_wpfa_template_file_active( 'page-past-events.php' ) ) {
			wp_enqueue_style( $this->plugin_name . '-past-events' );
		}

		// Events template (page template or CPT archive).
		if ( $this->is_wpfa_template_file_active( 'page-events.php' ) || is_post_type_archive( 'wpfa_event' ) ) {
			wp_enqueue_style( $this->plugin_name . '-events' );
			wp_enqueue_script( $this->plugin_name . '-events' );
		}

		if ( is_singular( 'wpfa_event' ) ) {
			wp_enqueue_style( $this->plugin_name . '-events' );
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
		*         plugin_dir_url( __DIR__ ) . 'public/css/templates/speakers.css',
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
