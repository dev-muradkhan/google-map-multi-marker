<?php
/**
 * Handles AJAX requests for marker management.
 *
 * @package GoogleMapMultiMarker
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// --- AJAX Action Hooks ---
add_action( 'wp_ajax_gmap_mm_add_marker', 'gmap_mm_ajax_add_marker' );
add_action( 'wp_ajax_gmap_mm_edit_marker', 'gmap_mm_ajax_edit_marker' );
add_action( 'wp_ajax_gmap_mm_delete_marker', 'gmap_mm_ajax_delete_marker' );
add_action( 'wp_ajax_gmap_mm_import_markers', 'gmap_mm_ajax_import_markers' );


/**
 * AJAX handler for adding a new marker.
 */
function gmap_mm_ajax_add_marker() {
	// Security check
	check_ajax_referer( 'gmap_mm_save_markers', 'nonce' ); // Check nonce passed from JS

	// Capability check
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'google-map-multi-marker' ) ), 403 );
	}

    // Get data from POST request
    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    $marker_input = isset( $_POST['marker'] ) && is_array( $_POST['marker'] ) ? wp_unslash( $_POST['marker'] ) : null;

    // Validate Post ID
    if ( ! $post_id || 'gmap_map' !== get_post_type( $post_id ) ) {
         wp_send_json_error( array( 'message' => __( 'Invalid Map ID.', 'google-map-multi-marker' ) ) );
    }

    if ( ! $marker_input ) {
        wp_send_json_error( array( 'message' => __( 'Invalid marker data received.', 'google-map-multi-marker' ) ) );
    }

    // Sanitize and validate marker data
    $sanitized_marker = array();
    $sanitized_marker['id'] = isset( $marker_input['id'] ) ? sanitize_text_field( $marker_input['id'] ) : 'marker_' . wp_generate_uuid4(); // Assign new UUID if ID not present
    $sanitized_marker['title'] = isset( $marker_input['title'] ) ? sanitize_text_field( $marker_input['title'] ) : '';
    $sanitized_marker['address'] = isset( $marker_input['address'] ) ? sanitize_text_field( $marker_input['address'] ) : ''; // Consider wp_kses_post for more complex addresses if needed
    $sanitized_marker['phone'] = isset( $marker_input['phone'] ) ? sanitize_text_field( $marker_input['phone'] ) : '';
    $sanitized_marker['web_link'] = isset( $marker_input['web_link'] ) ? esc_url_raw( $marker_input['web_link'] ) : '';
    $sanitized_marker['marker_image'] = isset( $marker_input['marker_image'] ) ? esc_url_raw( $marker_input['marker_image'] ) : '';
    $sanitized_marker['tooltip_image'] = isset( $marker_input['tooltip_image'] ) ? esc_url_raw( $marker_input['tooltip_image'] ) : '';

    // Validate coordinates (required)
    if ( ! isset( $marker_input['latitude'] ) || ! isset( $marker_input['longitude'] ) || $marker_input['latitude'] === '' || $marker_input['longitude'] === '' ) {
         wp_send_json_error( array( 'message' => __( 'Latitude and Longitude are required.', 'google-map-multi-marker' ) ) );
    }
    // Basic numeric check for lat/lng
    if ( ! is_numeric( $marker_input['latitude'] ) || ! is_numeric( $marker_input['longitude'] ) ) {
         wp_send_json_error( array( 'message' => __( 'Latitude and Longitude must be valid numbers.', 'google-map-multi-marker' ) ) );
    }
    $sanitized_marker['latitude'] = floatval( $marker_input['latitude'] ); // Store as float
    $sanitized_marker['longitude'] = floatval( $marker_input['longitude'] ); // Store as float


    // Get existing markers
    $markers = get_post_meta( $post_id, '_gmap_markers', true );
    $markers = is_array( $markers ) ? $markers : array();

    // Add the new marker
    $markers[] = $sanitized_marker;

    // Update post meta
    $update_result = update_post_meta( $post_id, '_gmap_markers', $markers );

    if ( false === $update_result ) {
         wp_send_json_error( array( 'message' => __( 'Failed to save marker data.', 'google-map-multi-marker' ) ) );
    }

    // Send back the sanitized marker data (including the potentially new ID)
	wp_send_json_success( array(
        'message' => __( 'Marker added successfully.', 'google-map-multi-marker' ),
        'marker'  => $sanitized_marker
    ) );

	wp_die(); // this is required to terminate immediately and return a proper response
}

/**
 * AJAX handler for editing an existing marker.
 */
function gmap_mm_ajax_edit_marker() {
	// Security check
	check_ajax_referer( 'gmap_mm_save_markers', 'nonce' );

	// Capability check
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'google-map-multi-marker' ) ), 403 );
	}

    // Get data from POST request
    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    $marker_id_to_edit = isset( $_POST['marker_id'] ) ? sanitize_text_field( $_POST['marker_id'] ) : null; // Unique ID of the marker to edit
    $marker_input = isset( $_POST['marker'] ) && is_array( $_POST['marker'] ) ? wp_unslash( $_POST['marker'] ) : null;

    // Validate Post ID and Marker ID
    if ( ! $post_id || 'gmap_map' !== get_post_type( $post_id ) ) {
         wp_send_json_error( array( 'message' => __( 'Invalid Map ID.', 'google-map-multi-marker' ) ) );
    }
     if ( ! $marker_id_to_edit ) {
        wp_send_json_error( array( 'message' => __( 'Invalid Marker ID for editing.', 'google-map-multi-marker' ) ) );
    }
    if ( ! $marker_input ) {
        wp_send_json_error( array( 'message' => __( 'Invalid marker data received.', 'google-map-multi-marker' ) ) );
    }

     // Sanitize and validate marker data (similar to add function)
    $sanitized_marker = array();
    $sanitized_marker['id'] = $marker_id_to_edit; // Keep the existing ID
    $sanitized_marker['title'] = isset( $marker_input['title'] ) ? sanitize_text_field( $marker_input['title'] ) : '';
    $sanitized_marker['address'] = isset( $marker_input['address'] ) ? sanitize_text_field( $marker_input['address'] ) : '';
    $sanitized_marker['phone'] = isset( $marker_input['phone'] ) ? sanitize_text_field( $marker_input['phone'] ) : '';
    $sanitized_marker['web_link'] = isset( $marker_input['web_link'] ) ? esc_url_raw( $marker_input['web_link'] ) : '';
    $sanitized_marker['marker_image'] = isset( $marker_input['marker_image'] ) ? esc_url_raw( $marker_input['marker_image'] ) : '';
    $sanitized_marker['tooltip_image'] = isset( $marker_input['tooltip_image'] ) ? esc_url_raw( $marker_input['tooltip_image'] ) : '';

    // Validate coordinates
    if ( ! isset( $marker_input['latitude'] ) || ! isset( $marker_input['longitude'] ) || $marker_input['latitude'] === '' || $marker_input['longitude'] === '' ) {
         wp_send_json_error( array( 'message' => __( 'Latitude and Longitude are required.', 'google-map-multi-marker' ) ) );
    }
    if ( ! is_numeric( $marker_input['latitude'] ) || ! is_numeric( $marker_input['longitude'] ) ) {
         wp_send_json_error( array( 'message' => __( 'Latitude and Longitude must be valid numbers.', 'google-map-multi-marker' ) ) );
    }
    $sanitized_marker['latitude'] = floatval( $marker_input['latitude'] );
    $sanitized_marker['longitude'] = floatval( $marker_input['longitude'] );

    // Get existing markers
    $markers = get_post_meta( $post_id, '_gmap_markers', true );
    $markers = is_array( $markers ) ? $markers : array();

    $marker_found = false;
    // Find and update the marker
    foreach ( $markers as $index => $marker ) {
        if ( isset( $marker['id'] ) && $marker['id'] === $marker_id_to_edit ) {
            $markers[ $index ] = $sanitized_marker; // Replace the old marker data with sanitized new data
            $marker_found = true;
            break;
        }
    }

    if ( ! $marker_found ) {
        wp_send_json_error( array( 'message' => __( 'Marker to edit not found.', 'google-map-multi-marker' ) ) );
    }

    // Update post meta
    $update_result = update_post_meta( $post_id, '_gmap_markers', $markers );

    if ( false === $update_result ) {
         wp_send_json_error( array( 'message' => __( 'Failed to save updated marker data.', 'google-map-multi-marker' ) ) );
    }

	wp_send_json_success( array(
        'message' => __( 'Marker updated successfully.', 'google-map-multi-marker' ),
        'marker'  => $sanitized_marker // Send back the updated data
    ) );
	wp_die();
}

/**
 * AJAX handler for deleting a marker.
 */
function gmap_mm_ajax_delete_marker() {
	// Security check
	check_ajax_referer( 'gmap_mm_save_markers', 'nonce' ); // Use the same nonce as add/edit

	// Capability check
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'google-map-multi-marker' ) ), 403 );
	}

    // Get data from POST request
    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    $marker_id_to_delete = isset( $_POST['marker_id'] ) ? sanitize_text_field( $_POST['marker_id'] ) : null; // Unique ID of the marker to delete

    // Validate Post ID and Marker ID
    if ( ! $post_id || 'gmap_map' !== get_post_type( $post_id ) ) {
         wp_send_json_error( array( 'message' => __( 'Invalid Map ID.', 'google-map-multi-marker' ) ) );
    }
     if ( ! $marker_id_to_delete ) {
        wp_send_json_error( array( 'message' => __( 'Invalid Marker ID for deletion.', 'google-map-multi-marker' ) ) );
    }

    // Get existing markers
    $markers = get_post_meta( $post_id, '_gmap_markers', true );
    $markers = is_array( $markers ) ? $markers : array();

    $marker_found = false;
    $updated_markers = array();

    // Find and remove the marker
    foreach ( $markers as $marker ) {
        if ( isset( $marker['id'] ) && $marker['id'] === $marker_id_to_delete ) {
            $marker_found = true;
            // Skip this marker, effectively deleting it
        } else {
            $updated_markers[] = $marker; // Keep other markers
        }
    }

    if ( ! $marker_found ) {
        // Although the JS prevents deleting non-existent rows, add a check just in case.
        wp_send_json_error( array( 'message' => __( 'Marker to delete not found.', 'google-map-multi-marker' ) ) );
    }

    // Update post meta with the filtered array
    $update_result = update_post_meta( $post_id, '_gmap_markers', $updated_markers );

    // Note: update_post_meta returns true on successful update,
    // or false on failure. It might also return true if the new value is the same as the old one.
    // We check for false explicitly.
    if ( false === $update_result ) {
         wp_send_json_error( array( 'message' => __( 'Failed to save updated marker data after deletion.', 'google-map-multi-marker' ) ) );
    }

	wp_send_json_success( array( 'message' => __( 'Marker deleted successfully.', 'google-map-multi-marker' ) ) );
	wp_die();
}

/**
 * AJAX handler for importing markers from CSV.
 */
function gmap_mm_ajax_import_markers() {
    // Security checks
    check_ajax_referer( 'gmap_mm_import_markers', 'nonce' ); // Check the import-specific nonce
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'google-map-multi-marker' ) ), 403 );
	}

    // Validate Post ID
    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    if ( ! $post_id || 'gmap_map' !== get_post_type( $post_id ) ) {
         wp_send_json_error( array( 'message' => __( 'Invalid Map ID.', 'google-map-multi-marker' ) ) );
    }

    // --- File Upload Handling ---
    if ( ! isset( $_FILES['csv_file'] ) || ! is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) {
         wp_send_json_error( array( 'message' => __( 'No CSV file uploaded or upload error.', 'google-map-multi-marker' ) ) );
    }

    // Check for upload errors
    if ( $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
        wp_send_json_error( array( 'message' => __( 'File upload error code: ', 'google-map-multi-marker' ) . $_FILES['csv_file']['error'] ) );
    }

    // Basic file type check (MIME type can be spoofed, but it's a first step)
    $allowed_mime_types = array( 'text/csv', 'text/plain', 'application/csv', 'text/comma-separated-values', 'application/excel', 'application/vnd.ms-excel', 'application/vnd.msexcel', 'text/anytext', 'application/octet-stream', 'application/txt' );
    $file_info = finfo_open( FILEINFO_MIME_TYPE );
    $mime_type = finfo_file( $file_info, $_FILES['csv_file']['tmp_name'] );
    finfo_close( $file_info );

    if ( ! in_array( $mime_type, $allowed_mime_types ) ) {
         wp_send_json_error( array( 'message' => __( 'Invalid file type. Please upload a valid CSV file.', 'google-map-multi-marker' ) . ' (Detected: ' . $mime_type . ')' ) );
    }

    // --- CSV Parsing ---
    $csv_file_path = $_FILES['csv_file']['tmp_name'];
    $imported_markers = array();
    $row_count = 0;
    $errors = array();

    // Increase time limit for potentially large files
    set_time_limit(300);

    if ( ( $handle = fopen( $csv_file_path, 'r' ) ) !== false ) {
        $header = fgetcsv( $handle ); // Read the header row
        if ( ! $header ) {
             wp_send_json_error( array( 'message' => __( 'Could not read CSV header.', 'google-map-multi-marker' ) ) );
        }
        // Normalize header names (lowercase, replace spaces with underscores)
        $header = array_map( function( $col ) { return strtolower( str_replace( ' ', '_', trim( $col ) ) ); }, $header );

        // Find column indices (case-insensitive search for common variations)
        $lat_col = array_search( 'latitude', $header );
        if ( $lat_col === false ) $lat_col = array_search( 'lat', $header );

        $lng_col = array_search( 'longitude', $header );
         if ( $lng_col === false ) $lng_col = array_search( 'lng', $header );
         if ( $lng_col === false ) $lng_col = array_search( 'lon', $header );

        $title_col = array_search( 'title', $header );
        $address_col = array_search( 'address', $header );
        $phone_col = array_search( 'phone', $header );
        $weblink_col = array_search( 'web_link', $header );
        if ( $weblink_col === false ) $weblink_col = array_search( 'website', $header );
        if ( $weblink_col === false ) $weblink_col = array_search( 'url', $header );
        $image_col = array_search( 'marker_image', $header );
         if ( $image_col === false ) $image_col = array_search( 'image_url', $header );
         if ( $image_col === false ) $image_col = array_search( 'image', $header );
        $tooltip_image_col = array_search( 'tooltip_image', $header );
        if ( $tooltip_image_col === false ) $tooltip_image_col = array_search( 'tooltip_image_url', $header );


        // Check if required columns (lat, lng) exist
        if ( $lat_col === false || $lng_col === false ) {
             wp_send_json_error( array( 'message' => __( 'CSV file must contain "latitude" and "longitude" columns.', 'google-map-multi-marker' ) ) );
        }

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $row_count++;
            $marker_input = array();

            // Extract data based on column indices
            $marker_input['latitude'] = isset( $row[ $lat_col ] ) ? trim( $row[ $lat_col ] ) : '';
            $marker_input['longitude'] = isset( $row[ $lng_col ] ) ? trim( $row[ $lng_col ] ) : '';
            $marker_input['title'] = ( $title_col !== false && isset( $row[ $title_col ] ) ) ? trim( $row[ $title_col ] ) : '';
            $marker_input['address'] = ( $address_col !== false && isset( $row[ $address_col ] ) ) ? trim( $row[ $address_col ] ) : '';
            $marker_input['phone'] = ( $phone_col !== false && isset( $row[ $phone_col ] ) ) ? trim( $row[ $phone_col ] ) : '';
            $marker_input['web_link'] = ( $weblink_col !== false && isset( $row[ $weblink_col ] ) ) ? trim( $row[ $weblink_col ] ) : '';
            $marker_input['marker_image'] = ( $image_col !== false && isset( $row[ $image_col ] ) ) ? trim( $row[ $image_col ] ) : '';
            $marker_input['tooltip_image'] = ( $tooltip_image_col !== false && isset( $row[ $tooltip_image_col ] ) ) ? trim( $row[ $tooltip_image_col ] ) : '';


            // --- Sanitize and Validate Row Data ---
            $sanitized_marker = array();
            $sanitized_marker['id'] = 'marker_' . wp_generate_uuid4(); // Generate new ID for each imported marker
            $sanitized_marker['title'] = sanitize_text_field( $marker_input['title'] );
            $sanitized_marker['address'] = sanitize_text_field( $marker_input['address'] );
            $sanitized_marker['phone'] = sanitize_text_field( $marker_input['phone'] );
            $sanitized_marker['web_link'] = esc_url_raw( $marker_input['web_link'] );
            $sanitized_marker['marker_image'] = esc_url_raw( $marker_input['marker_image'] );
            $sanitized_marker['tooltip_image'] = esc_url_raw( $marker_input['tooltip_image'] );

            // Validate coordinates
            if ( $marker_input['latitude'] === '' || $marker_input['longitude'] === '' ) {
                $errors[] = sprintf( __( 'Skipped row %d: Missing latitude or longitude.', 'google-map-multi-marker' ), $row_count );
                continue; // Skip this row
            }
            if ( ! is_numeric( $marker_input['latitude'] ) || ! is_numeric( $marker_input['longitude'] ) ) {
                 $errors[] = sprintf( __( 'Skipped row %d: Invalid latitude or longitude (must be numeric).', 'google-map-multi-marker' ), $row_count );
                continue; // Skip this row
            }
            $sanitized_marker['latitude'] = floatval( $marker_input['latitude'] );
            $sanitized_marker['longitude'] = floatval( $marker_input['longitude'] );

            // Add valid marker to the list
            $imported_markers[] = $sanitized_marker;
        }
        fclose( $handle );
    } else {
         wp_send_json_error( array( 'message' => __( 'Could not open CSV file for reading.', 'google-map-multi-marker' ) ) );
    }

    // --- Update Post Meta ---
    if ( ! empty( $imported_markers ) ) {
        $existing_markers = get_post_meta( $post_id, '_gmap_markers', true );
        $existing_markers = is_array( $existing_markers ) ? $existing_markers : array();

        // Merge existing and new markers
        $all_markers = array_merge( $existing_markers, $imported_markers );

        $update_result = update_post_meta( $post_id, '_gmap_markers', $all_markers );

        if ( false === $update_result ) {
             wp_send_json_error( array( 'message' => __( 'Failed to save imported marker data.', 'google-map-multi-marker' ) ) );
        }
    }

    // --- Prepare Response ---
    $success_message = sprintf(
        _n( '%d marker imported successfully.', '%d markers imported successfully.', count( $imported_markers ), 'google-map-multi-marker' ),
        count( $imported_markers )
    );
    if ( ! empty( $errors ) ) {
        $success_message .= ' ' . __( 'Some rows were skipped:', 'google-map-multi-marker' ) . "\n" . implode( "\n", $errors );
    }


    wp_send_json_success( array(
        'message'          => $success_message,
        'imported_markers' => $imported_markers // Send back the newly added markers for JS
    ) );
    wp_die();
}

?>
