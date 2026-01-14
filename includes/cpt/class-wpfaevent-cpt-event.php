<?php
/**
 * Registers the Event Custom Post Type.
 *
 * @package    WPFAevent
 * @subpackage WPFAevent/includes/cpt
 * @author     FOSSASIA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Wpfaevent_CPT_Event.
 *
 * Registers the `wpfa_event` custom post type.
 */
class Wpfaevent_CPT_Event {

	/**
	 * The custom post type key.
	 *
	 * @var string
	 */
	public static $post_type = 'wpfa_event';

	/**
	 * Registers the custom post type.
	 *
	 * @since 1.0.0
	 */
	public static function register() {

		$labels = array(
			'name'                  => _x( 'Events', 'Post Type General Name', 'wpfaevent' ),
			'singular_name'         => _x( 'Event', 'Post Type Singular Name', 'wpfaevent' ),
			'menu_name'             => __( 'Events', 'wpfaevent' ),
			'name_admin_bar'        => __( 'Event', 'wpfaevent' ),
			'archives'              => __( 'Event Archives', 'wpfaevent' ),
			'attributes'            => __( 'Event Attributes', 'wpfaevent' ),
			'parent_item_colon'     => __( 'Parent Event:', 'wpfaevent' ),
			'all_items'             => __( 'All Events', 'wpfaevent' ),
			'add_new_item'          => __( 'Add New Event', 'wpfaevent' ),
			'add_new'               => __( 'Add New', 'wpfaevent' ),
			'new_item'              => __( 'New Event', 'wpfaevent' ),
			'edit_item'             => __( 'Edit Event', 'wpfaevent' ),
			'update_item'           => __( 'Update Event', 'wpfaevent' ),
			'view_item'             => __( 'View Event', 'wpfaevent' ),
			'view_items'            => __( 'View Events', 'wpfaevent' ),
			'search_items'          => __( 'Search Event', 'wpfaevent' ),
			'not_found'             => __( 'Not found', 'wpfaevent' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'wpfaevent' ),
			'featured_image'        => __( 'Featured Image', 'wpfaevent' ),
			'set_featured_image'    => __( 'Set featured image', 'wpfaevent' ),
			'remove_featured_image' => __( 'Remove featured image', 'wpfaevent' ),
			'use_featured_image'    => __( 'Use as featured image', 'wpfaevent' ),
			'insert_into_item'      => __( 'Insert into event', 'wpfaevent' ),
			'uploaded_to_this_item' => __( 'Uploaded to this event', 'wpfaevent' ),
			'items_list'            => __( 'Events list', 'wpfaevent' ),
			'items_list_navigation' => __( 'Events list navigation', 'wpfaevent' ),
			'filter_items_list'     => __( 'Filter events list', 'wpfaevent' ),
		);

		$capabilities = array(
			'edit_post'          => 'edit_event',
			'read_post'          => 'read_event',
			'delete_post'        => 'delete_event',
			'edit_posts'         => 'edit_events',
			'edit_others_posts'  => 'edit_others_events',
			'publish_posts'      => 'publish_events',
			'read_private_posts' => 'read_private_events',
		);

		$args = array(
			'label'               => __( 'Event', 'wpfaevent' ),
			'description'         => __( 'Event Custom Post Type', 'wpfaevent' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-calendar-alt',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'capabilities'        => $capabilities,
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
			'rewrite'             => array( 'slug' => 'events' ),
		);

		register_post_type( self::$post_type, $args );
	}
}
