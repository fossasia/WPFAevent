<?php
/**
 * Registers custom taxonomies for the plugin.
 *
 * @package    WPFAevent
 * @subpackage WPFAevent/includes
 * @author     FOSSASIA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WPFA_Event_Taxonomies.
 *
 * Registers taxonomies for the Event CPT.
 */
class WPFA_Event_Taxonomies {

	/**
	 * Registers all taxonomies.
	 */
	public static function register_taxonomies() {
		self::register_track_taxonomy();
		self::register_tag_taxonomy();
	}

	/**
	 * Registers the 'Track' taxonomy.
	 */
	private static function register_track_taxonomy() {
		$labels = array(
			'name'              => _x( 'Tracks', 'Taxonomy General Name', 'wpfaevent' ),
			'singular_name'     => _x( 'Track', 'Taxonomy Singular Name', 'wpfaevent' ),
			'menu_name'         => __( 'Tracks', 'wpfaevent' ),
			'all_items'         => __( 'All Tracks', 'wpfaevent' ),
			'parent_item'       => __( 'Parent Track', 'wpfaevent' ),
			'parent_item_colon' => __( 'Parent Track:', 'wpfaevent' ),
			'new_item_name'     => __( 'New Track Name', 'wpfaevent' ),
			'add_new_item'      => __( 'Add New Track', 'wpfaevent' ),
			'edit_item'         => __( 'Edit Track', 'wpfaevent' ),
			'update_item'       => __( 'Update Track', 'wpfaevent' ),
			'view_item'         => __( 'View Track', 'wpfaevent' ),
			'search_items'      => __( 'Search Tracks', 'wpfaevent' ),
			'not_found'         => __( 'Not Found', 'wpfaevent' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => false,
			'rewrite'           => array( 'slug' => 'event-track' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( 'wpfaevent_track', array( 'wpfaevent_event' ), $args );
	}

	/**
	 * Registers the 'Tag' taxonomy.
	 */
	private static function register_tag_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Tags', 'Taxonomy General Name', 'wpfaevent' ),
			'singular_name'              => _x( 'Tag', 'Taxonomy Singular Name', 'wpfaevent' ),
			'menu_name'                  => __( 'Tags', 'wpfaevent' ),
			'all_items'                  => __( 'All Tags', 'wpfaevent' ),
			'new_item_name'              => __( 'New Tag Name', 'wpfaevent' ),
			'add_new_item'               => __( 'Add New Tag', 'wpfaevent' ),
			'edit_item'                  => __( 'Edit Tag', 'wpfaevent' ),
			'update_item'                => __( 'Update Tag', 'wpfaevent' ),
			'view_item'                  => __( 'View Tag', 'wpfaevent' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'wpfaevent' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'wpfaevent' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'wpfaevent' ),
			'popular_items'              => __( 'Popular Tags', 'wpfaevent' ),
			'search_items'               => __( 'Search Tags', 'wpfaevent' ),
			'not_found'                  => __( 'Not Found', 'wpfaevent' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite'           => array( 'slug' => 'event-tag' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( 'wpfaevent_tag', array( 'wpfaevent_event' ), $args );
	}
}