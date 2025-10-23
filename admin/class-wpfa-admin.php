<?php
if ( ! class_exists( 'WPFA_Admin' ) ) {

	class WPFA_Admin {

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}

		public function add_admin_menu() {
			add_menu_page(
				__( 'FOSSASIA Event', 'wpfa-event' ),
				__( 'FOSSASIA Event', 'wpfa-event' ),
				'manage_options',
				'wpfa-settings',
				array( $this, 'render_settings_page' ),
				'dashicons-calendar-alt'
			);
		}

		public function register_settings() {
			register_setting( 'wpfa_settings_group', 'wpfa_image_base_path', array(
				'sanitize_callback' => 'esc_url_raw',
			) );

			register_setting( 'wpfa_settings_group', 'wpfa_default_placeholder', array(
				'sanitize_callback' => 'sanitize_text_field',
			) );

			register_setting( 'wpfa_settings_group', 'wpfa_feature_toggle', array(
				'sanitize_callback' => 'absint',
			) );
		}

		public function render_settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'FOSSASIA Plugin Settings', 'wpfa-event' ); ?></h1>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'wpfa_settings_group' );
					do_settings_sections( 'wpfa_settings_group' );
					?>
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Image Base Path', 'wpfa-event' ); ?></th>
							<td><input type="url" name="wpfa_image_base_path" value="<?php echo esc_attr( get_option( 'wpfa_image_base_path' ) ); ?>" class="regular-text"></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Default Placeholder Image', 'wpfa-event' ); ?></th>
							<td><input type="text" name="wpfa_default_placeholder" value="<?php echo esc_attr( get_option( 'wpfa_default_placeholder' ) ); ?>" class="regular-text"></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Enable Extra Features', 'wpfa-event' ); ?></th>
							<td><input type="checkbox" name="wpfa_feature_toggle" value="1" <?php checked( 1, get_option( 'wpfa_feature_toggle', 0 ) ); ?>></td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</form>
			</div>
			<?php
		}
	}
}