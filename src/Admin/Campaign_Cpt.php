<?php

namespace WPRobo_Engage_Lite\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Campaign_Cpt
 *
 * Handles the registration of the 'wpr_campaign' Custom Post Type.
 *
 * @package WPRobo_Engage_Lite\Admin
 */
class Campaign_Cpt {

	/**
	 * Registers the Custom Post Type.
	 * This method is hooked into the 'init' action.
	 *
	 * @return void
	 */
	public function register_cpt(): void {
		$labels = array(
			'name'                  => esc_html_x( 'Campaigns', 'Post Type General Name', 'wprobo-engage-lite' ),
			'singular_name'         => esc_html_x( 'Campaign', 'Post Type Singular Name', 'wprobo-engage-lite' ),
			'menu_name'             => esc_html__( 'WPRobo Engage', 'wprobo-engage-lite' ),
			'name_admin_bar'        => esc_html__( 'Campaign', 'wprobo-engage-lite' ),
			'archives'              => esc_html__( 'Campaign Archives', 'wprobo-engage-lite' ),
			'attributes'            => esc_html__( 'Campaign Attributes', 'wprobo-engage-lite' ),
			'parent_item_colon'     => esc_html__( 'Parent Campaign:', 'wprobo-engage-lite' ),
			'all_items'             => esc_html__( 'All Campaigns', 'wprobo-engage-lite' ),
			'add_new_item'          => esc_html__( 'Add New Campaign', 'wprobo-engage-lite' ),
			'add_new'               => esc_html__( 'Add New', 'wprobo-engage-lite' ),
			'new_item'              => esc_html__( 'New Campaign', 'wprobo-engage-lite' ),
			'edit_item'             => esc_html__( 'Edit Campaign', 'wprobo-engage-lite' ),
			'update_item'           => esc_html__( 'Update Campaign', 'wprobo-engage-lite' ),
			'view_item'             => esc_html__( 'View Campaign', 'wprobo-engage-lite' ),
			'view_items'            => esc_html__( 'View Campaigns', 'wprobo-engage-lite' ),
			'search_items'          => esc_html__( 'Search Campaign', 'wprobo-engage-lite' ),
			'not_found'             => esc_html__( 'Not found', 'wprobo-engage-lite' ),
			'not_found_in_trash'    => esc_html__( 'Not found in Trash', 'wprobo-engage-lite' ),
			'featured_image'        => esc_html__( 'Featured Image', 'wprobo-engage-lite' ),
			'set_featured_image'    => esc_html__( 'Set featured image', 'wprobo-engage-lite' ),
			'remove_featured_image' => esc_html__( 'Remove featured image', 'wprobo-engage-lite' ),
			'use_featured_image'    => esc_html__( 'Use as featured image', 'wprobo-engage-lite' ),
			'insert_into_item'      => esc_html__( 'Insert into campaign', 'wprobo-engage-lite' ),
			'uploaded_to_this_item' => esc_html__( 'Uploaded to this campaign', 'wprobo-engage-lite' ),
			'items_list'            => esc_html__( 'Campaigns list', 'wprobo-engage-lite' ),
			'items_list_navigation' => esc_html__( 'Campaigns list navigation', 'wprobo-engage-lite' ),
			'filter_items_list'     => esc_html__( 'Filter campaigns list', 'wprobo-engage-lite' ),
		);

		$args = array(
			'label'               => esc_html__( 'Campaign', 'wprobo-engage-lite' ),
			'description'         => esc_html__( 'Post Type for WPRobo Engage Campaigns', 'wprobo-engage-lite' ),
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => false, // Not publicly visible on the frontend website.
			'show_ui'             => true,
			// We now point this to the slug of our new top-level menu page.
			'show_in_menu'        => 'wprobo-engage',
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-megaphone',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'page',
			'show_in_rest'        => true,
		);

		register_post_type( 'wpr_campaign', $args );
	}
}
