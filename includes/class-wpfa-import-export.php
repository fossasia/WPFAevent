<?php
/**
 * Handles Import/Export functionality.
 *
 * @package FOSSASIA-Event-Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPFA_Import_Export.
 */
class WPFA_Import_Export {

	/**
	 * Render the Import/Export page.
	 */
	public static function render_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p>Import and Export functionality will be implemented here.</p>
		</div>
		<?php
	}
}