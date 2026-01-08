<?php
/**
 * Registers custom taxonomies for the plugin.
 *
 * @package    WPFAevent
 * @subpackage WPFAevent/includes/taxonomies
 * @author     FOSSASIA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Wpfaevent_Taxonomies.
 *
 * Registers taxonomies for the Event CPT.
 */
class Wpfaevent_Taxonomies {

	/**
	 * Registers all taxonomies.
	 */
	public static function register() {
		self::register_track_taxonomy();
		self::register_tag_taxonomy();
	}

	/**
	 * Registers the 'Track' taxonomy for events.
	 * 
	 * Hierarchical taxonomy for organizing events by track
	 * (e.g., "AI Track", "Web Development Track").
	 */
	private static function register_track_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Tracks', 'taxonomy general name', 'wpfaevent' ),
			'singular_name'              => _x( 'Track', 'taxonomy singular name', 'wpfaevent' ),
			'menu_name'                  => __( 'Tracks', 'wpfaevent' ),
			'all_items'                  => __( 'All Tracks', 'wpfaevent' ),
			'parent_item'                => __( 'Parent Track', 'wpfaevent' ),
			'parent_item_colon'          => __( 'Parent Track:', 'wpfaevent' ),
			'new_item_name'              => __( 'New Track Name', 'wpfaevent' ),
			'add_new_item'               => __( 'Add New Track', 'wpfaevent' ),
			'edit_item'                  => __( 'Edit Track', 'wpfaevent' ),
			'update_item'                => __( 'Update Track', 'wpfaevent' ),
			'view_item'                  => __( 'View Track', 'wpfaevent' ),
			'separate_items_with_commas' => __( 'Separate tracks with commas', 'wpfaevent' ),
			'add_or_remove_items'        => __( 'Add or remove tracks', 'wpfaevent' ),
			'choose_from_most_used'      => __( 'Choose from the most used tracks', 'wpfaevent' ),
			'popular_items'              => __( 'Popular Tracks', 'wpfaevent' ),
			'search_items'               => __( 'Search Tracks', 'wpfaevent' ),
			'not_found'                  => __( 'No tracks found', 'wpfaevent' ),
			'no_terms'                   => __( 'No tracks', 'wpfaevent' ),
			'items_list'                 => __( 'Tracks list', 'wpfaevent' ),
			'items_list_navigation'      => __( 'Tracks list navigation', 'wpfaevent' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => false,
			'show_in_rest'      => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'event-track' ),
		);

		register_taxonomy( 'wpfa_event_track', array( 'wpfa_event' ), $args );
	}

	/**
	 * Registers the 'Tag' taxonomy for events.
	 * 
	 * Non-hierarchical taxonomy for tagging events
	 * (e.g., "beginner-friendly", "hands-on", "keynote").
	 */
	private static function register_tag_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Event Tags', 'taxonomy general name', 'wpfaevent' ),
			'singular_name'              => _x( 'Event Tag', 'taxonomy singular name', 'wpfaevent' ),
			'menu_name'                  => __( 'Event Tags', 'wpfaevent' ),
			'all_items'                  => __( 'All Tags', 'wpfaevent' ),
			'new_item_name'              => __( 'New Tag Name', 'wpfaevent' ),
			'add_new_item'               => __( 'Add New Tag', 'wpfaevent' ),
			'edit_item'                  => __( 'Edit Tag', 'wpfaevent' ),
			'update_item'                => __( 'Update Tag', 'wpfaevent' ),
			'view_item'                  => __( 'View Tag', 'wpfaevent' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'wpfaevent' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'wpfaevent' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'wpfaevent' ),
			'popular_items'              => __( 'Popular Tags', 'wpfaevent' ),
			'search_items'               => __( 'Search Tags', 'wpfaevent' ),
			'not_found'                  => __( 'No tags found', 'wpfaevent' ),
			'no_terms'                   => __( 'No tags', 'wpfaevent' ),
			'items_list'                 => __( 'Tags list', 'wpfaevent' ),
			'items_list_navigation'      => __( 'Tags list navigation', 'wpfaevent' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'event-tag' ),
		);

		register_taxonomy( 'wpfa_event_tag', array( 'wpfa_event' ), $args );
	}
}