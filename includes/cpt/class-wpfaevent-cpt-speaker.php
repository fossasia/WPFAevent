<?php
/**
 * Registers the Speaker Custom Post Type.
 *
 * @package    WPFAevent
 * @subpackage WPFAevent/includes/cpt
 * @author     FOSSASIA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Wpfaevent_CPT_Speaker.
 *
 * Registers the `wpfa_speaker` custom post type.
 */
class Wpfaevent_CPT_Speaker {

	/**
	 * The custom post type key.
	 *
	 * @var string
	 */
	public static $post_type = 'wpfa_speaker';

	/**
	 * Registers the custom post type.
	 */
	public static function register() {
		
		$labels = array(
			'name'                  => _x( 'Speakers', 'Post Type General Name', 'wpfaevent' ),
			'singular_name'         => _x( 'Speaker', 'Post Type Singular Name', 'wpfaevent' ),
			'menu_name'             => __( 'Speakers', 'wpfaevent' ),
			'name_admin_bar'        => __( 'Speaker', 'wpfaevent' ),
			'archives'              => __( 'Speaker Archives', 'wpfaevent' ),
			'attributes'            => __( 'Speaker Attributes', 'wpfaevent' ),
			'parent_item_colon'     => __( 'Parent Speaker:', 'wpfaevent' ),
			'all_items'             => __( 'All Speakers', 'wpfaevent' ),
			'add_new_item'          => __( 'Add New Speaker', 'wpfaevent' ),
			'add_new'               => __( 'Add New', 'wpfaevent' ),
			'new_item'              => __( 'New Speaker', 'wpfaevent' ),
			'edit_item'             => __( 'Edit Speaker', 'wpfaevent' ),
			'update_item'           => __( 'Update Speaker', 'wpfaevent' ),
			'view_item'             => __( 'View Speaker', 'wpfaevent' ),
			'view_items'            => __( 'View Speakers', 'wpfaevent' ),
			'search_items'          => __( 'Search Speaker', 'wpfaevent' ),
			'not_found'             => __( 'Not found', 'wpfaevent' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'wpfaevent' ),
			'featured_image'        => __( 'Featured Image', 'wpfaevent' ),
			'set_featured_image'    => __( 'Set featured image', 'wpfaevent' ),
			'remove_featured_image' => __( 'Remove featured image', 'wpfaevent' ),
			'use_featured_image'    => __( 'Use as featured image', 'wpfaevent' ),
			'insert_into_item'      => __( 'Insert into speaker', 'wpfaevent' ),
			'uploaded_to_this_item' => __( 'Uploaded to this speaker', 'wpfaevent' ),
			'items_list'            => __( 'Speakers list', 'wpfaevent' ),
			'items_list_navigation' => __( 'Speakers list navigation', 'wpfaevent' ),
			'filter_items_list'     => __( 'Filter speakers list', 'wpfaevent' ),
		);

		$capabilities = array(
			'edit_post'          => 'edit_speaker',
			'read_post'          => 'read_speaker',
			'delete_post'        => 'delete_speaker',
			'edit_posts'         => 'edit_speakers',
			'edit_others_posts'  => 'edit_others_speakers',
			'publish_posts'      => 'publish_speakers',
			'read_private_posts' => 'read_private_speakers',
		);

		$args = array(
			'label'               => __( 'Speaker', 'wpfaevent' ),
			'description'         => __( 'Speaker Custom Post Type', 'wpfaevent' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 6,
			'menu_icon'           => 'dashicons-microphone',
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
			'rewrite'             => array( 'slug' => 'speakers' ),
		);

		register_post_type( self::$post_type, $args );
	}
}