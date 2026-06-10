<?php
/**
 * WPFAevent roles and capabilities.
 *
 * Three access levels:
 * - Administrator: full WordPress site control plus all plugin capabilities.
 * - Event Organizer: import from Eventyay and publish/manage events and speakers.
 * - Event Contributor: maintain existing event and speaker content without import or publish.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register plugin capabilities and custom event roles.
 */
class Wpfaevent_Roles {

	/**
	 * Stored roles schema version.
	 *
	 * @var string
	 */
	const ROLES_VERSION = '1.1.0';

	/**
	 * Option key for the roles schema version.
	 *
	 * @var string
	 */
	const ROLES_VERSION_OPTION = 'wpfaevent_roles_version';

	/**
	 * Event staff who can import and publish.
	 *
	 * @var string
	 */
	const ROLE_ORGANIZER = 'wpfa_event_organizer';

	/**
	 * Event staff with limited content-management access.
	 *
	 * @var string
	 */
	const ROLE_CONTRIBUTOR = 'wpfa_event_contributor';

	/**
	 * Capability for the WPFAEvent settings screen.
	 *
	 * @var string
	 */
	const CAP_MANAGE_SETTINGS = 'manage_wpfa_settings';

	/**
	 * Capability for saving Eventyay import settings and running imports.
	 *
	 * @var string
	 */
	const CAP_IMPORT_EVENTYAY = 'import_eventyay_events';

	/**
	 * Bootstrap roles on plugin load.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_upgrade_roles' ), 5 );
		add_filter( 'option_page_capability_wpfaevent_eventyay_import', array( __CLASS__, 'filter_eventyay_settings_capability' ) );
	}

	/**
	 * Upgrade roles for existing installs without reactivation.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function maybe_upgrade_roles() {
		$installed_version = get_option( self::ROLES_VERSION_OPTION, '' );

		if ( self::ROLES_VERSION === $installed_version ) {
			return;
		}

		self::register_roles_and_capabilities();
		update_option( self::ROLES_VERSION_OPTION, self::ROLES_VERSION, false );
	}

	/**
	 * Register capabilities and plugin roles.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_roles_and_capabilities() {
		self::register_organizer_role();
		self::register_contributor_role();
		self::grant_administrator_capabilities();
	}

	/**
	 * Capability required to save Eventyay import settings.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function filter_eventyay_settings_capability() {
		return self::CAP_IMPORT_EVENTYAY;
	}

	/**
	 * Whether the current user can access WPFAEvent plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function current_user_can_manage_settings() {
		return current_user_can( self::CAP_MANAGE_SETTINGS );
	}

	/**
	 * Whether the current user can configure or run Eventyay imports.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function current_user_can_import_eventyay() {
		return current_user_can( self::CAP_IMPORT_EVENTYAY );
	}

	/**
	 * Whether the current user can maintain existing event and speaker content.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function current_user_can_manage_dashboard() {
		return current_user_can( 'edit_events' ) && current_user_can( 'edit_speakers' );
	}

	/**
	 * Whether the current user can create or publish new events and speakers.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function current_user_can_publish_content() {
		return current_user_can( 'publish_events' ) && current_user_can( 'publish_speakers' );
	}

	/**
	 * Whether the current user can delete events or speakers.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function current_user_can_delete_content() {
		return current_user_can( 'delete_events' ) && current_user_can( 'delete_speakers' );
	}

	/**
	 * Whether the current user can manage site-wide footer branding.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function current_user_can_manage_site_branding() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Frontend script capability flags shared across events and speakers pages.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, bool>
	 */
	public static function get_frontend_script_capabilities() {
		return array(
			'isAdmin'               => self::current_user_can_publish_content(),
			'canManageContent'      => self::current_user_can_manage_dashboard(),
			'canDeleteContent'      => self::current_user_can_delete_content(),
			'canManageSiteBranding' => self::current_user_can_manage_site_branding(),
		);
	}

	/**
	 * Full event CPT capabilities for organizers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public static function get_event_capabilities() {
		return array(
			'edit_event',
			'read_event',
			'delete_event',
			'edit_events',
			'edit_others_events',
			'publish_events',
			'read_private_events',
			'delete_events',
			'delete_private_events',
			'delete_published_events',
			'delete_others_events',
			'edit_private_events',
			'edit_published_events',
		);
	}

	/**
	 * Full speaker CPT capabilities for organizers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public static function get_speaker_capabilities() {
		return array(
			'edit_speaker',
			'read_speaker',
			'delete_speaker',
			'edit_speakers',
			'edit_others_speakers',
			'publish_speakers',
			'read_private_speakers',
			'delete_speakers',
			'delete_private_speakers',
			'delete_published_speakers',
			'delete_others_speakers',
			'edit_private_speakers',
			'edit_published_speakers',
		);
	}

	/**
	 * Plugin-level capabilities granted to organizers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public static function get_plugin_capabilities() {
		return array(
			self::CAP_MANAGE_SETTINGS,
			self::CAP_IMPORT_EVENTYAY,
		);
	}

	/**
	 * All capabilities assigned to the Event Organizer role.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public static function get_organizer_capabilities() {
		return array_values(
			array_unique(
				array_merge(
					self::get_event_capabilities(),
					self::get_speaker_capabilities(),
					self::get_plugin_capabilities()
				)
			)
		);
	}

	/**
	 * Capabilities assigned to the Event Contributor role.
	 *
	 * Contributors can update existing event and speaker content, but cannot
	 * import from Eventyay, publish new posts, or delete content.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public static function get_contributor_capabilities() {
		return array(
			'read_event',
			'edit_event',
			'edit_events',
			'edit_others_events',
			'edit_published_events',
			'edit_private_events',
			'read_private_events',
			'read_speaker',
			'edit_speaker',
			'edit_speakers',
			'edit_others_speakers',
			'edit_published_speakers',
			'edit_private_speakers',
			'read_private_speakers',
		);
	}

	/**
	 * Create or update the Event Organizer role.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function register_organizer_role() {
		self::register_role( self::ROLE_ORGANIZER, __( 'Event Organizer', 'wpfaevent' ), self::get_organizer_capabilities() );
	}

	/**
	 * Create or update the Event Contributor role.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function register_contributor_role() {
		self::register_role( self::ROLE_CONTRIBUTOR, __( 'Event Contributor', 'wpfaevent' ), self::get_contributor_capabilities() );
	}

	/**
	 * Create or update one plugin role.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $role_key     Role slug.
	 * @param string              $display_name Human-readable role name.
	 * @param array<int, string>  $capabilities Capabilities to grant.
	 * @return void
	 */
	private static function register_role( $role_key, $display_name, $capabilities ) {
		$capability_map = array_fill_keys( $capabilities, true );
		$role           = get_role( $role_key );

		if ( $role instanceof WP_Role ) {
			foreach ( $capability_map as $capability => $granted ) {
				if ( $granted ) {
					$role->add_cap( $capability );
				}
			}

			return;
		}

		add_role( $role_key, $display_name, $capability_map );
	}

	/**
	 * Ensure administrators retain the plugin capabilities.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function grant_administrator_capabilities() {
		$role = get_role( 'administrator' );

		if ( ! $role instanceof WP_Role ) {
			return;
		}

		foreach ( self::get_organizer_capabilities() as $capability ) {
			$role->add_cap( $capability );
		}
	}
}
