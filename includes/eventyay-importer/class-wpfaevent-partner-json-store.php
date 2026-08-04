<?php
/**
 * Eventyay Partner JSON Store.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/eventyay-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles reading and writing partner and speaker data JSON files.
 */
class Wpfaevent_Partner_Json_Store {

	/**
	 * Read a dashboard JSON file from the uploads data directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename File name.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function read_dashboard_json_file( $filename, $fallback ) {
		$path = $this->get_dashboard_json_path( $filename );
		if ( is_wp_error( $path ) ) {
			return $fallback;
		}

		$filesystem = $this->get_wp_filesystem();
		if ( is_wp_error( $filesystem ) || ! $filesystem->exists( $path ) ) {
			return $fallback;
		}

		$contents = $filesystem->get_contents( $path );
		if ( false === $contents || '' === trim( $contents ) ) {
			return $fallback;
		}

		$decoded = json_decode( $contents, true );

		return ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : $fallback;
	}

	/**
	 * Write a dashboard JSON file into the uploads data directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename File name.
	 * @param mixed  $data     Data to write.
	 * @return true|WP_Error
	 */
	public function write_dashboard_json_file( $filename, $data ) {
		$path = $this->get_dashboard_json_path( $filename );
		if ( is_wp_error( $path ) ) {
			return $path;
		}

		$filesystem = $this->get_wp_filesystem();
		if ( is_wp_error( $filesystem ) ) {
			return $filesystem;
		}

		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return new WP_Error(
				'eventyay_json_encode_failed',
				esc_html__( 'Could not encode synced Eventyay dashboard data.', 'wpfaevent' ),
				array( 'status' => 500 )
			);
		}

		$chmod_file = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
		if ( ! $filesystem->put_contents( $path, $json, $chmod_file ) ) {
			return new WP_Error(
				'eventyay_json_write_failed',
				esc_html__( 'Could not write synced Eventyay dashboard data to the dashboard data file.', 'wpfaevent' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Get a safe dashboard JSON path under the uploads data directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename File name.
	 * @return string|WP_Error
	 */
	public function get_dashboard_json_path( $filename ) {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error(
				'eventyay_upload_dir_failed',
				esc_html__( 'Could not access the WordPress uploads directory.', 'wpfaevent' ),
				array(
					'status'  => 500,
					'details' => $upload_dir['error'],
				)
			);
		}

		$data_dir = trailingslashit( $upload_dir['basedir'] ) . 'fossasia-data';
		if ( ! wp_mkdir_p( $data_dir ) ) {
			return new WP_Error(
				'eventyay_data_dir_failed',
				esc_html__( 'Could not create the dashboard data directory.', 'wpfaevent' ),
				array( 'status' => 500 )
			);
		}

		return trailingslashit( $data_dir ) . sanitize_file_name( $filename );
	}

	/**
	 * Initialize and return the WordPress filesystem API.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Filesystem_Base|WP_Error
	 */
	public function get_wp_filesystem() {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() || ! $wp_filesystem ) {
			return new WP_Error(
				'eventyay_filesystem_failed',
				esc_html__( 'Could not initialize the WordPress filesystem.', 'wpfaevent' ),
				array( 'status' => 500 )
			);
		}

		return $wp_filesystem;
	}
}
