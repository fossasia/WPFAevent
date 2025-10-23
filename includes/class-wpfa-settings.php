<?php
if ( ! class_exists( 'WPFA_Settings' ) ) {

	class WPFA_Settings {
		public static function get( $key, $default = '' ) {
			return get_option( $key, $default );
		}

		public static function update( $key, $value ) {
			update_option( $key, $value );
		}
	}
}