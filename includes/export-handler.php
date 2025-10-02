<?php
/**
 * Handles CSV Export functionality for map markers.
 *
 * @package GoogleMapMultiMarker
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Listens for the export request and triggers the CSV download.
 */
function gmap_mm_handle_csv_export() {
	// Check if our export action has been triggered.
	if ( ! isset( $_GET['action'] ) || 'gmap_mm_export_csv' !== $_GET['action'] ) {
		return;
	}

	// Verify the nonce for security.
	if ( ! isset( $_GET['gmap_mm_nonce'] ) || ! wp_verify_nonce( $_GET['gmap_mm_nonce'], 'gmap_mm_export_csv_nonce' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'google-map-multi-marker' ) );
	}

	// Get and validate the Post ID.
	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

	// Check user permissions.
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to export markers for this map.', 'google-map-multi-marker' ) );
	}

	// Fetch the markers from the database.
	$markers = get_post_meta( $post_id, '_gmap_markers', true );

	// Check if there are any markers to export.
	if ( empty( $markers ) || ! is_array( $markers ) ) {
		wp_die( esc_html__( 'There are no markers to export for this map.', 'google-map-multi-marker' ) );
	}

	// --- Generate and Download CSV ---

	// Set a dynamic filename.
	$filename = 'map-' . $post_id . '-markers-' . date( 'Y-m-d' ) . '.csv';

	// Set the HTTP headers to force a browser download.
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	// Create a file pointer connected to the output stream.
	$output = fopen( 'php://output', 'w' );

	// Define the CSV header row. This must match the columns the import function recognizes.
	$header_row = [
		'title',
		'address',
		'latitude',
		'longitude',
		'phone',
		'web_link',
		'marker_image',
		'tooltip_image',
	];
	fputcsv( $output, $header_row );

	// Loop through the markers and write each one to the CSV file.
	foreach ( $markers as $marker ) {
		$row = [
			$marker['title'] ?? '',
			$marker['address'] ?? '',
			$marker['latitude'] ?? '',
			$marker['longitude'] ?? '',
			$marker['phone'] ?? '',
			$marker['web_link'] ?? '',
			$marker['marker_image'] ?? '',
			$marker['tooltip_image'] ?? '',
		];
		fputcsv( $output, $row );
	}

	// Close the file pointer.
	fclose( $output );

	// Terminate the script to prevent any other output.
	exit;
}
add_action( 'admin_init', 'gmap_mm_handle_csv_export' );
