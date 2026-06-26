<?php
/**
 * Eventyay dashboard JSON storage helpers.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read and write the Eventyay dashboard JSON files.
 */
class Wpfaevent_Eventyay_Dashboard_Store {

	/**
	 * Read a dashboard JSON file.
	 *
	 * @param string $filename File name.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function read_dashboard_json_file( $filename, $fallback ) {
		$path = $this->get_dashboard_json_path( $filename );
		if ( ! $path ) {
			return $fallback;
		}

		$filesystem = $this->get_wp_filesystem();
		if ( ! $filesystem || ! $filesystem->exists( $path ) ) {
			return $fallback;
		}

		$content = $filesystem->get_contents( $path );
		if ( false === $content || '' === trim( (string) $content ) ) {
			return $fallback;
		}

		$decoded = json_decode( $content, true );

		return JSON_ERROR_NONE === json_last_error() ? $decoded : $fallback;
	}

	/**
	 * Write a dashboard JSON file.
	 *
	 * @param string $filename File name.
	 * @param mixed  $data Data to write.
	 * @return true|WP_Error
	 */
	public function write_dashboard_json_file( $filename, $data ) {
		$path = $this->get_dashboard_json_path( $filename );
		if ( ! $path ) {
			return new WP_Error(
				'wpfaevent_dashboard_json_missing_path',
				esc_html__( 'Could not resolve the dashboard data file path.', 'wpfaevent' )
			);
		}

		$filesystem = $this->get_wp_filesystem();
		if ( ! $filesystem ) {
			return new WP_Error(
				'wpfaevent_dashboard_json_filesystem',
				esc_html__( 'Could not initialize the WordPress filesystem.', 'wpfaevent' )
			);
		}

		$encoded = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( false === $encoded ) {
			return new WP_Error(
				'wpfaevent_dashboard_json_encode',
				esc_html__( 'Could not encode dashboard data.', 'wpfaevent' )
			);
		}

		$directory = trailingslashit( dirname( $path ) );
		if ( ! $filesystem->is_dir( $directory ) ) {
			if ( ! $filesystem->mkdir( $directory, FS_CHMOD_DIR ) ) {
				return new WP_Error(
					'wpfaevent_dashboard_json_mkdir',
					esc_html__( 'Could not create the dashboard data directory.', 'wpfaevent' )
				);
			}
		}

		if ( false === $filesystem->put_contents( $path, $encoded, FS_CHMOD_FILE ) ) {
			return new WP_Error(
				'wpfaevent_dashboard_json_write',
				esc_html__( 'Could not write dashboard data.', 'wpfaevent' )
			);
		}

		return true;
	}

	/**
	 * Resolve a dashboard JSON path.
	 *
	 * @param string $filename File name.
	 * @return string
	 */
	public function get_dashboard_json_path( $filename ) {
		$base_dir = wp_upload_dir();
		if ( empty( $base_dir['basedir'] ) ) {
			return '';
		}

		return trailingslashit( $base_dir['basedir'] ) . 'fossasia-data/' . ltrim( sanitize_file_name( $filename ), '/' );
	}

	/**
	 * Get the WordPress filesystem object.
	 *
	 * @return WP_Filesystem_Base|false
	 */
	public function get_wp_filesystem() {
		global $wp_filesystem;

		if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
			return $wp_filesystem;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			return false;
		}

		if ( ! WP_Filesystem() ) {
			return false;
		}

		global $wp_filesystem;

		return $wp_filesystem instanceof WP_Filesystem_Base ? $wp_filesystem : false;
	}
}
