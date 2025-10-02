<?php
/**
 * Handles the [map-multi-marker] shortcode.
 *
 * @package GoogleMapMultiMarker
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register the shortcode.
 */
function gmap_mm_register_shortcode() {
	add_shortcode( 'map-multi-marker', 'gmap_mm_render_shortcode' );
}
add_action( 'init', 'gmap_mm_register_shortcode' );

/**
 * Render the map via the shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output for the map container.
 */
function gmap_mm_render_shortcode( $atts ) {
	// If in Elementor editor, let the editor script handle it via data attributes in elementor-widget.php
	if ( did_action( 'elementor/loaded' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
		// The elementor-widget.php render() method handles output for the editor.
		// Return empty here as the widget's render method provides the necessary container.
		return '';
	}

	$atts = shortcode_atts(
		array(
			'id' => 0, // Default map ID
		),
		$atts,
		'map-multi-marker'
	);

	$map_id = absint( $atts['id'] );

	// Validate Map ID
	if ( ! $map_id || 'gmap_map' !== get_post_type( $map_id ) || 'publish' !== get_post_status( $map_id ) ) {
		// Optionally return an error message for logged-in admins
        if ( current_user_can('edit_posts') ) {
            return '<p style="color: red;">' . esc_html__( 'Google Map Multi-Marker Error: Invalid Map ID or Map not published.', 'google-map-multi-marker' ) . '</p>';
        }
		return ''; // Return empty string for public view if invalid
	}

	// Get map data
	$map_options = get_post_meta( $map_id, '_gmap_options', true );
	$markers     = get_post_meta( $map_id, '_gmap_markers', true );
    $api_key     = get_option( 'gmap_api_key' );

    // Ensure data is in expected format
    $map_options = is_array($map_options) ? $map_options : [];
    $markers     = is_array($markers) ? $markers : [];

    // Check for API Key
    if ( empty( $api_key ) ) {
        if ( current_user_can('edit_posts') ) {
            return '<p style="color: red;">' . esc_html__( 'Google Map Multi-Marker Error: Google Maps API Key is missing. Please configure it in the plugin settings.', 'google-map-multi-marker' ) . '</p>';
        }
        return ''; // Don't show map if API key is missing
    }

    // Generate a unique ID for this map instance ONCE
    $map_instance_suffix = wp_rand(100, 999);
    $map_container_id = 'gmap-mm-container-' . esc_attr( $map_id ) . '-' . $map_instance_suffix;

	// Localize data for this specific map instance and ensure scripts are enqueued if needed (for non-Elementor frontend)
	gmap_mm_enqueue_and_localize_for_map( $map_id, $map_options, $markers, $map_container_id );

	// Prepare map container HTML using the generated ID
    $width = isset($map_options['width']) ? esc_attr($map_options['width']) : '100%';
    $height = isset($map_options['height']) ? esc_attr($map_options['height']) : '400px';

	$output = '<div id="' . $map_container_id . '" class="gmap-mm-container" style="width: ' . $width . '; height: ' . $height . ';">';
	$output .= '<p>' . esc_html__( 'Loading map...', 'google-map-multi-marker' ) . '</p>'; // Placeholder text
	$output .= '</div>';

	return $output;
}


/**
 * Register frontend scripts.
 * This ensures WordPress and Elementor know about the scripts.
 * This function should ONLY run on the actual frontend, not in the Elementor editor.
 * It only REGISTERS the scripts/styles. Enqueuing happens in gmap_mm_enqueue_and_localize_for_map.
 */
function gmap_mm_register_frontend_scripts() {
    // No editor checks needed here; wp_enqueue_scripts hook doesn't run in editor ajax requests.

    $api_key = get_option( 'gmap_api_key' );
    if ( empty( $api_key ) ) {
        return; // Don't register if no API key
    }

    // Register Google Maps API script
    // Note: The callback 'gmapMmInitMaps' needs to be defined globally in frontend-map.js
    // Register the main frontend map script FIRST
    if ( ! wp_script_is( 'gmap-mm-frontend-script', 'registered' ) ) {
         wp_register_script(
            'gmap-mm-frontend-script',
            GMAP_MM_PLUGIN_URL . 'assets/js/frontend-map.js',
            array( 'jquery' ), // Only depends on jQuery now
            GMAP_MM_VERSION,
            true // Load in footer
        );
        // DO NOT localize here - localization happens only when the map is rendered on the frontend.
    }

    // Register Google Maps API script SECOND, making it depend on our script
    // Remove the callback parameter - we'll initiate manually from frontend-map.js
    if ( ! wp_script_is( 'google-maps-api', 'registered' ) ) {
        wp_register_script(
            'google-maps-api',
            'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&libraries=marker&v=beta', // No callback=
            array( 'gmap-mm-frontend-script' ), // Depends on our script now
            null,
            true // Load in footer
        );
    }

     // Register basic styles (optional, but good practice) - No changes needed here
    if ( ! wp_style_is( 'gmap-mm-frontend-style', 'registered' ) ) {
        wp_register_style(
            'gmap-mm-frontend-style',
            GMAP_MM_PLUGIN_URL . 'assets/css/frontend-map.css',
            array(),
            GMAP_MM_VERSION
        );
    }
}
// Register scripts on the appropriate hook for the frontend ONLY
add_action( 'wp_enqueue_scripts', 'gmap_mm_register_frontend_scripts' );

/**
 * Register and Enqueue scripts specifically for the Elementor Editor.
 */
function gmap_mm_enqueue_editor_scripts() {
    error_log('[GMM Editor DBG] PHP: gmap_mm_enqueue_editor_scripts function CALLED.'); // PHP Debug Log
    $api_key = get_option( 'gmap_api_key' );
    if ( empty( $api_key ) ) {
        // Maybe add an admin notice here if needed
        return;
    }

    // Register Google Maps API FIRST
    $editor_api_handle = 'google-maps-api-editor';
    if ( ! wp_script_is( $editor_api_handle, 'registered' ) ) {
         wp_register_script(
            $editor_api_handle,
            'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&libraries=marker&v=beta',
            array(), // No dependencies needed here for editor script
            null,
            array( 'in_footer' => true ) // Load in footer
        );
    }

    // Register the Editor-specific script SECOND, adding the dependency back
    wp_register_script(
        'gmap-mm-editor-script',
        GMAP_MM_PLUGIN_URL . 'assets/js/google-map-editor.js',
        array( 'jquery', 'elementor-frontend', $editor_api_handle ), // Add dependency back
        GMAP_MM_VERSION,
        true // Load in footer
    );

    // Scripts are now registered. Elementor should enqueue them based on get_script_depends() in the widget.
    // We still need to enqueue the style here if needed.

    // Enqueue frontend styles if needed in editor (often helpful for consistency)
    // Register if not already registered before enqueueing style
    if ( ! wp_style_is( 'gmap-mm-frontend-style', 'registered' ) ) {
         wp_register_style(
            'gmap-mm-frontend-style',
            GMAP_MM_PLUGIN_URL . 'assets/css/frontend-map.css', // Assuming you have this CSS
            array(),
            GMAP_MM_VERSION
        );
    }
    wp_enqueue_style('gmap-mm-frontend-style');

}
// Use 'preview/enqueue_scripts' hook for the editor preview iframe
add_action( 'elementor/preview/enqueue_scripts', 'gmap_mm_enqueue_editor_scripts' );

// REMOVED async filter again - focusing on getting map to load first.


/**
 * Enqueue necessary scripts/styles and localize data for a specific map instance (FRONTEND ONLY).
 * Called during shortcode rendering on the actual frontend.
 *
 * @param int    $map_id The ID of the map post.
 * @param array  $map_options Map settings.
 * @param array  $markers Array of marker data.
 * @param string $map_container_id The unique HTML ID for the map container.
 */
function gmap_mm_enqueue_and_localize_for_map( $map_id, $map_options, $markers, $map_container_id ) {

    // This function is now only called on the frontend due to the check in gmap_mm_render_shortcode.
    // Enqueue the scripts and styles needed for the frontend map.
    wp_enqueue_style('gmap-mm-frontend-style');
    wp_enqueue_script('gmap-mm-frontend-script');
    wp_enqueue_script('google-maps-api'); // Ensure Google Maps API is enqueued too

    // --- Localization (for gmap-mm-frontend-script) ---

    // Get existing localized data (if any)
    // wp_localize_script appends if called multiple times for the same handle,
    // but we need to manage the 'maps' array ourselves.
    $localized_data_key = 'gmapMmData';
    $existing_data_php = wp_scripts()->get_data( 'gmap-mm-frontend-script', 'data' );

    // If data exists, it's a string like 'var gmapMmData = {...};'
    if ( ! empty( $existing_data_php ) && is_string( $existing_data_php ) ) {
        // Extract JSON part and decode
        $json_string = trim( str_replace( 'var ' . $localized_data_key . ' =', '', $existing_data_php ) );
        if ( substr( $json_string, -1 ) === ';' ) {
            $json_string = substr( $json_string, 0, -1 );
        }
        $decoded_data = json_decode( $json_string, true );
        if ( json_last_error() === JSON_ERROR_NONE && isset( $decoded_data['maps'] ) && is_array( $decoded_data['maps'] ) ) {
            $existing_data_php = $decoded_data;
        } else {
            // Fallback if decoding fails or 'maps' key is missing/invalid
            $existing_data_php = array( 'maps' => [] );
        }
    } else {
        // Initialize if no data was previously localized
    $existing_data_php = array( 'maps' => [] );
    }

    // Get global default marker and tooltip images
    $global_default_marker_icon = get_option( 'gmap_mm_default_marker_icon', GMAP_MM_DEFAULT_MARKER_ICON );
    $global_default_tooltip_image = get_option( 'gmap_mm_default_tooltip_image', GMAP_MM_DEFAULT_TOOLTIP_IMAGE );

    // Use the container ID passed from the render function
    $current_map_data = array(
        'mapId' => $map_id,
        'containerId' => $map_container_id, // Use the consistent container ID
        'options' => $map_options,
        'markers' => $markers,
        'defaultMarkerIcon' => $global_default_marker_icon,
        'defaultTooltipImage' => $global_default_tooltip_image,
    );

    // Append current map data to the existing maps array
    $existing_data_php['maps'][] = $current_map_data;

    // Debugging: Log the map options before localization
    error_log( 'GMAP_MM Debug: Localizing map data for container ' . $map_container_id . ': ' . print_r( $current_map_data, true ) );

    // Re-localize the script with the updated data
    // This will overwrite the previous localization for 'gmapMmData' for this script handle.
    // Subsequent calls to wp_localize_script for the same handle will append to the data.
    wp_localize_script( 'gmap-mm-frontend-script', $localized_data_key, $existing_data_php );
}

?>
