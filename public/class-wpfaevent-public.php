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
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/wpfaevent-public.css', array(), $this->version, 'all' );

		// Navigation component (shared across templates)
		wp_enqueue_style(
			$this->plugin_name . '-navigation',
			plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/components/navigation.css',
			array( $this->plugin_name ),
			$this->version,
			'all'
		);

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
