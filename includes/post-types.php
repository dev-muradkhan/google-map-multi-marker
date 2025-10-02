<?php
/**
 * Registers the Custom Post Type for Maps.
 *
 * @package GoogleMapMultiMarker
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register the 'gmap_map' Custom Post Type.
 */
function gmap_mm_register_map_cpt() {

	$labels = array(
		'name'                  => _x( 'Maps', 'Post Type General Name', 'google-map-multi-marker' ),
		'singular_name'         => _x( 'Map', 'Post Type Singular Name', 'google-map-multi-marker' ),
		'menu_name'             => __( 'Google Maps', 'google-map-multi-marker' ),
		'name_admin_bar'        => __( 'Map', 'google-map-multi-marker' ),
		'archives'              => __( 'Map Archives', 'google-map-multi-marker' ),
		'attributes'            => __( 'Map Attributes', 'google-map-multi-marker' ),
		'parent_item_colon'     => __( 'Parent Map:', 'google-map-multi-marker' ),
		'all_items'             => __( 'All Maps', 'google-map-multi-marker' ),
		'add_new_item'          => __( 'Add New Map', 'google-map-multi-marker' ),
		'add_new'               => __( 'Add New', 'google-map-multi-marker' ),
		'new_item'              => __( 'New Map', 'google-map-multi-marker' ),
		'edit_item'             => __( 'Edit Map', 'google-map-multi-marker' ),
		'update_item'           => __( 'Update Map', 'google-map-multi-marker' ),
		'view_item'             => __( 'View Map', 'google-map-multi-marker' ),
		'view_items'            => __( 'View Maps', 'google-map-multi-marker' ),
		'search_items'          => __( 'Search Map', 'google-map-multi-marker' ),
		'not_found'             => __( 'Not found', 'google-map-multi-marker' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'google-map-multi-marker' ),
		'featured_image'        => __( 'Featured Image', 'google-map-multi-marker' ),
		'set_featured_image'    => __( 'Set featured image', 'google-map-multi-marker' ),
		'remove_featured_image' => __( 'Remove featured image', 'google-map-multi-marker' ),
		'use_featured_image'    => __( 'Use as featured image', 'google-map-multi-marker' ),
		'insert_into_item'      => __( 'Insert into map', 'google-map-multi-marker' ),
		'uploaded_to_this_item' => __( 'Uploaded to this map', 'google-map-multi-marker' ),
		'items_list'            => __( 'Maps list', 'google-map-multi-marker' ),
		'items_list_navigation' => __( 'Maps list navigation', 'google-map-multi-marker' ),
		'filter_items_list'     => __( 'Filter maps list', 'google-map-multi-marker' ),
	);
	$args = array(
		'label'                 => __( 'Map', 'google-map-multi-marker' ),
		'description'           => __( 'Custom Post Type for Google Maps with multiple markers.', 'google-map-multi-marker' ),
		'labels'                => $labels,
		// 'editor' support removed as settings are handled in meta boxes. Title support is kept.
		'supports'              => array( 'title' ),
		'hierarchical'          => false,
		'public'                => false, // Not publicly queryable on frontend directly via slug.
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 25, // Below Comments
		'menu_icon'             => 'dashicons-location-alt',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => false,
		'capability_type'       => 'post',
        'show_in_rest'          => false, // Disable Gutenberg editor for this CPT for now.
	);
	register_post_type( 'gmap_map', $args );

}
add_action( 'init', 'gmap_mm_register_map_cpt', 0 );

?>
