<?php
/**
 * Registers custom taxonomies for Speaker CPT.
 *
 * @package    WPFAevent
 * @subpackage WPFAevent/includes/taxonomies
 * @author     FOSSASIA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Wpfaevent_Taxonomies_Speaker.
 *
 * Registers taxonomies for the Speaker CPT.
 */
class Wpfaevent_Taxonomies_Speaker {

	/**
	 * Registers all speaker taxonomies.
	 *
	 * @since 1.0.0
	 */
	public static function register() {
		self::register_speaker_category_taxonomy();
	}

	/**
	 * Registers the 'Speaker Category' taxonomy.
	 *
	 * Non-hierarchical taxonomy for categorizing speakers
	 * (e.g., "AI", "Web Development", "Cloud", "Open Source").
	 *
	 * @since 1.0.0
	 */
	private static function register_speaker_category_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Speaker Categories', 'taxonomy general name', 'wpfaevent' ),
			'singular_name'              => _x( 'Speaker Category', 'taxonomy singular name', 'wpfaevent' ),
			'menu_name'                  => __( 'Categories', 'wpfaevent' ),
			'all_items'                  => __( 'All Categories', 'wpfaevent' ),
			'new_item_name'              => __( 'New Category Name', 'wpfaevent' ),
			'add_new_item'               => __( 'Add New Category', 'wpfaevent' ),
			'edit_item'                  => __( 'Edit Category', 'wpfaevent' ),
			'update_item'                => __( 'Update Category', 'wpfaevent' ),
			'view_item'                  => __( 'View Category', 'wpfaevent' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'wpfaevent' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'wpfaevent' ),
			'choose_from_most_used'      => __( 'Choose from the most used categories', 'wpfaevent' ),
			'popular_items'              => __( 'Popular Categories', 'wpfaevent' ),
			'search_items'               => __( 'Search Categories', 'wpfaevent' ),
			'not_found'                  => __( 'No categories found', 'wpfaevent' ),
			'no_terms'                   => __( 'No categories', 'wpfaevent' ),
			'items_list'                 => __( 'Categories list', 'wpfaevent' ),
			'items_list_navigation'      => __( 'Categories list navigation', 'wpfaevent' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false, // Like tags, not categories
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'speaker-category' ),
			'capabilities'      => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			),
		);

		register_taxonomy( 'wpfa_speaker_category', array( 'wpfa_speaker' ), $args );
	}
}
