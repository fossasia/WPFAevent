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
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<!-- main content -->
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<div class="postbox">
								<h2><span><?php esc_html_e( 'Export Data', 'wpfa-event' ); ?></span></h2>
								<div class="inside">
									<p><?php esc_html_e( 'Export Events and Speakers to a JSON file.', 'wpfa-event' ); ?></p>
									<form method="post">
										<input type="hidden" name="wpfa_action" value="export_data" />
										<?php wp_nonce_field( 'wpfa_export_nonce', 'wpfa_export_nonce' ); ?>
										<?php submit_button( __( 'Export All Data', 'wpfa-event' ) ); ?>
									</form>
								</div>
							</div>
							<div class="postbox">
								<h2><span><?php esc_html_e( 'Import Data', 'wpfa-event' ); ?></span></h2>
								<div class="inside">
									<p><?php esc_html_e( 'Import Events and Speakers from a JSON file. This will overwrite existing data with the same slugs.', 'wpfa-event' ); ?></p>
									<form method="post" enctype="multipart/form-data">
										<input type="hidden" name="wpfa_action" value="import_data" />
										<input type="file" name="wpfa_import_file" />
										<?php wp_nonce_field( 'wpfa_import_nonce', 'wpfa_import_nonce' ); ?>
										<?php submit_button( __( 'Import Data', 'wpfa-event' ) ); ?>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
		<?php
	}
}