<?php
/**
 * WPFAevent access levels and capabilities.
 *
 * Site administrators assign Event Organizer or Event Contributor access per user
 * under WPFAEvent -> Settings. WordPress user roles stay unchanged.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage plugin access levels and dynamic capabilities.
 */
class Wpfaevent_Roles {

	/**
	 * Stored access schema version.
	 *
	 * @var string
	 */
	const ROLES_VERSION = '1.2.0';

	/**
	 * Option key for the access schema version.
	 *
	 * @var string
	 */
	const ROLES_VERSION_OPTION = 'wpfaevent_roles_version';

	/**
	 * Option key for per-user plugin access assignments.
	 *
	 * @var string
	 */
	const ACCESS_LEVELS_OPTION = 'wpfaevent_user_access_levels';

	/**
	 * Settings group for plugin options.
	 *
	 * @var string
	 */
	const SETTINGS_GROUP = 'wpfaevent_plugin_settings';

	/**
	 * Legacy WordPress role slug kept only for migration.
	 *
	 * @var string
	 */
	const LEGACY_ROLE_ORGANIZER = 'wpfa_event_organizer';

	/**
	 * Legacy WordPress role slug kept only for migration.
	 *
	 * @var string
	 */
	const LEGACY_ROLE_CONTRIBUTOR = 'wpfa_event_contributor';

	/**
	 * Plugin access level for full event operations.
	 *
	 * @var string
	 */
	const ACCESS_ORGANIZER = 'organizer';

	/**
	 * Plugin access level for limited content maintenance.
	 *
	 * @var string
	 */
	const ACCESS_CONTRIBUTOR = 'contributor';

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
	 * Recursion guard for the user_has_cap filter.
	 *
	 * @var bool
	 */
	private static $in_capability_filter = false;

	/**
	 * Bootstrap access handling on plugin load.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_upgrade_roles' ), 5 );
		add_filter( 'user_has_cap', array( __CLASS__, 'filter_user_capabilities' ), 10, 4 );
		add_filter( 'option_page_capability_wpfaevent_eventyay_import', array( __CLASS__, 'filter_eventyay_settings_capability' ) );
		add_filter( 'option_page_capability_' . self::SETTINGS_GROUP, array( __CLASS__, 'filter_plugin_settings_capability' ) );
	}

	/**
	 * Upgrade access assignments for existing installs without reactivation.
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
	 * Register administrator capabilities and migrate legacy role assignments.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_roles_and_capabilities() {
		self::migrate_legacy_role_assignments();
		self::remove_legacy_roles();
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
	 * Capability required to save plugin access assignments.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function filter_plugin_settings_capability() {
		return 'manage_options';
	}

	/**
	 * Whether the current user can assign plugin access levels.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function current_user_can_manage_plugin_access() {
		return current_user_can( 'manage_options' );
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
	 * Valid plugin access level labels for settings UI.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public static function get_access_level_labels() {
		return array(
			''                       => __( 'No plugin access', 'wpfaevent' ),
			self::ACCESS_ORGANIZER   => __( 'Event Organizer', 'wpfaevent' ),
			self::ACCESS_CONTRIBUTOR => __( 'Event Contributor', 'wpfaevent' ),
		);
	}

	/**
	 * Get saved per-user plugin access assignments.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public static function get_user_access_levels() {
		$levels = get_option( self::ACCESS_LEVELS_OPTION, array() );

		if ( ! is_array( $levels ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $levels as $user_id => $level ) {
			$user_id = absint( $user_id );
			$level   = sanitize_key( $level );

			if ( ! $user_id || ! self::is_valid_access_level( $level ) ) {
				continue;
			}

			$normalized[ $user_id ] = $level;
		}

		return $normalized;
	}

	/**
	 * Sanitize plugin access assignments from the settings form.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $input Raw option input.
	 * @return array<int, string>
	 */
	public static function sanitize_user_access_levels( $input ) {
		if ( ! self::current_user_can_manage_plugin_access() ) {
			return self::get_user_access_levels();
		}

		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $input as $user_id => $level ) {
			$user_id = absint( $user_id );
			$level   = sanitize_key( $level );

			if ( ! $user_id || '' === $level ) {
				continue;
			}

			if ( ! self::is_valid_access_level( $level ) ) {
				continue;
			}

			$user = get_userdata( $user_id );
			if ( ! $user instanceof WP_User || self::user_is_site_administrator( $user ) ) {
				continue;
			}

			$sanitized[ $user_id ] = $level;
		}

		return $sanitized;
	}

	/**
	 * Grant plugin capabilities based on site admin status or saved assignments.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, bool> $allcaps All capabilities for the user.
	 * @param array<int, string>  $caps    Primitive capabilities being checked.
	 * @param array<int, mixed>   $args    Capability check arguments.
	 * @param WP_User             $user    User object.
	 * @return array<string, bool>
	 */
	public static function filter_user_capabilities( $allcaps, $caps, $args, $user ) {
		unset( $args );

		if ( self::$in_capability_filter ) {
			return $allcaps;
		}

		if ( ! $user instanceof WP_User || ! $user->ID ) {
			return $allcaps;
		}

		self::$in_capability_filter = true;

		if ( self::user_is_site_administrator( $user, $allcaps ) ) {
			foreach ( self::get_organizer_capabilities() as $capability ) {
				$allcaps[ $capability ] = true;
			}

			self::$in_capability_filter = false;
			return $allcaps;
		}

		$level = self::get_assigned_access_level( $user->ID );
		if ( '' === $level ) {
			self::$in_capability_filter = false;
			return $allcaps;
		}

		$grant_caps = self::ACCESS_ORGANIZER === $level
			? self::get_organizer_capabilities()
			: self::get_contributor_capabilities();

		foreach ( $grant_caps as $capability ) {
			$allcaps[ $capability ] = true;
		}

		self::$in_capability_filter = false;
		return $allcaps;
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
	 * All capabilities assigned to Event Organizer access.
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
	 * Capabilities assigned to Event Contributor access.
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
	 * Get the assigned plugin access level for one user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function get_assigned_access_level( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return '';
		}

		$levels = self::get_user_access_levels();

		return isset( $levels[ $user_id ] ) ? $levels[ $user_id ] : '';
	}

	/**
	 * Whether a user is a WordPress site administrator.
	 *
	 * Must not call has_cap() — this runs inside the user_has_cap filter.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User                  $user    User object.
	 * @param array<string, bool>|null $allcaps Optional capability map from user_has_cap.
	 * @return bool
	 */
	public static function user_is_site_administrator( $user, $allcaps = null ) {
		if ( is_array( $allcaps ) && ! empty( $allcaps['manage_options'] ) ) {
			return true;
		}

		if ( ! $user instanceof WP_User ) {
			return false;
		}

		if ( in_array( 'administrator', (array) $user->roles, true ) ) {
			return true;
		}

		return is_multisite() && is_super_admin( $user->ID );
	}

	/**
	 * Whether a value is a valid plugin access level.
	 *
	 * @since 1.0.0
	 *
	 * @param string $level Access level.
	 * @return bool
	 */
	private static function is_valid_access_level( $level ) {
		return in_array( $level, array( self::ACCESS_ORGANIZER, self::ACCESS_CONTRIBUTOR ), true );
	}

	/**
	 * Copy legacy custom WordPress role members into plugin assignments.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function migrate_legacy_role_assignments() {
		$levels  = self::get_user_access_levels();
		$changed = false;

		$legacy_map = array(
			self::LEGACY_ROLE_ORGANIZER   => self::ACCESS_ORGANIZER,
			self::LEGACY_ROLE_CONTRIBUTOR => self::ACCESS_CONTRIBUTOR,
		);

		foreach ( $legacy_map as $legacy_role => $access_level ) {
			$user_ids = get_users(
				array(
					'role'   => $legacy_role,
					'fields' => 'ID',
				)
			);

			foreach ( $user_ids as $user_id ) {
				$user_id = absint( $user_id );

				if ( ! $user_id || isset( $levels[ $user_id ] ) ) {
					continue;
				}

				$levels[ $user_id ] = $access_level;
				$changed            = true;
			}
		}

		if ( $changed ) {
			update_option( self::ACCESS_LEVELS_OPTION, $levels, false );
		}
	}

	/**
	 * Remove deprecated custom WordPress roles.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function remove_legacy_roles() {
		remove_role( self::LEGACY_ROLE_ORGANIZER );
		remove_role( self::LEGACY_ROLE_CONTRIBUTOR );
	}

	/**
	 * Ensure administrators retain plugin capabilities in role metadata.
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
