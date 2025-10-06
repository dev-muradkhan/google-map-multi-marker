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
 * Manages collecting and localizing data for all map instances on a single page.
 * This ensures multiple maps can be loaded without data conflicts.
 */
class Gmap_MM_Frontend_Data_Manager {
    /**
     * @var array Holds the data for each map to be displayed on the page.
     */
    private static $map_data = [];

    /**
     * @var bool Flag to ensure scripts are enqueued and hooks are added only once.
     */
    private static $scripts_initialized = false;

    /**
     * Adds data for a specific map to the queue and initializes scripts if needed.
     *
     * @param int    $map_id The ID of the map post.
     * @param array  $map_options Map settings.
     * @param array  $markers Array of marker data.
     * @param string $map_container_id The unique HTML ID for the map container.
     */
    public static function add_map_data( $map_id, $map_options, $markers, $map_container_id ) {
        // Only enqueue scripts and add the footer hook once per page load.
        if ( ! self::$scripts_initialized ) {
            wp_enqueue_style( 'gmap-mm-frontend-style' );
            wp_enqueue_script( 'gmap-mm-frontend-script' );
            wp_enqueue_script( 'google-maps-api' );

            // Hook the final data localization to the footer to ensure all shortcodes have run.
            add_action( 'wp_footer', [ __CLASS__, 'localize_script_data' ] );

            self::$scripts_initialized = true;
        }

        // Add the current map's data to our static array.
        self::$map_data[] = [
            'mapId'       => $map_id,
            'containerId' => $map_container_id,
            'options'     => $map_options,
            'markers'     => $markers,
        ];
    }

    /**
     * Localizes the collected map data to the frontend script.
     * This runs in the footer, passing a single object with all map data.
     */
    public static function localize_script_data() {
        if ( ! empty( self::$map_data ) ) {
            // =========================================================================
            // == MODIFICATION START: Added 'pluginUrl' to the localized data array. ==
            // =========================================================================
            $data_to_localize = [
                'maps'      => self::$map_data,
                'pluginUrl' => GMAP_MM_PLUGIN_URL // Pass the plugin URL constant to JS
            ];
            // =======================================================================
            // == MODIFICATION END                                                  ==
            // =======================================================================
            wp_localize_script( 'gmap-mm-frontend-script', 'gmapMmData', $data_to_localize );
        }
    }
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
	// If in Elementor editor, let the editor script handle it.
	if ( did_action( 'elementor/loaded' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
		return '';
	}

    // ** UPDATED: Add 'zoom' to the list of accepted attributes **
	$atts = shortcode_atts(
		array(
			'id'     => 0,
            'width'  => '',
            'height' => '',
            'zoom'   => '', // Accept zoom attribute
		),
		$atts,
		'map-multi-marker'
	);

	$map_id = absint( $atts['id'] );

	// Validate Map ID
	if ( ! $map_id || 'gmap_map' !== get_post_type( $map_id ) || ! in_array( get_post_status( $map_id ), [ 'publish', 'private' ] ) ) {
        if ( current_user_can( 'edit_posts' ) ) {
            return '<p style="color: red;">' . esc_html__( 'Google Map Multi-Marker Error: Invalid Map ID or Map not published.', 'google-map-multi-marker' ) . '</p>';
        }
		return '';
	}

	// Get map data
	$map_options = get_post_meta( $map_id, '_gmap_options', true );
	$markers     = get_post_meta( $map_id, '_gmap_markers', true );
    $api_key     = get_option( 'gmap_api_key' );

	$map_options = is_array( $map_options ) ? $map_options : [];
    $markers     = is_array( $markers ) ? $markers : [];

	// Check for API Key
    if ( empty( $api_key ) ) {
        if ( current_user_can( 'edit_posts' ) ) {
            return '<p style="color: red;">' . esc_html__( 'Google Map Multi-Marker Error: Google Maps API Key is missing. Please configure it in the plugin settings.', 'google-map-multi-marker' ) . '</p>';
        }
        return '';
    }

    // ** UPDATED: Apply zoom override from shortcode attribute before localizing data **
    if ( ! empty( $atts['zoom'] ) && is_numeric( $atts['zoom'] ) ) {
        $map_options['zoom'] = absint( $atts['zoom'] );
    }

    // Generate a unique container ID for this specific map instance
    $map_instance_suffix = wp_rand( 100, 999 );
    $map_container_id    = 'gmap-mm-container-' . esc_attr( $map_id ) . '-' . $map_instance_suffix;

    // ** REVISED LOGIC: Add this map's data to the central manager **
    Gmap_MM_Frontend_Data_Manager::add_map_data( $map_id, $map_options, $markers, $map_container_id );

	// Handle width and height overrides from shortcode attributes
    $width  = ! empty( $atts['width'] ) ? esc_attr( $atts['width'] ) : ( $map_options['width'] ?? '100%' );
    $height = ! empty( $atts['height'] ) ? esc_attr( $atts['height'] ) : ( $map_options['height'] ?? '400px' );

	// Generate the HTML output
	$output  = '<div id="' . $map_container_id . '" class="gmap-mm-container" style="width: ' . $width . '; height: ' . $height . ';">';
	$output .= '<p>' . esc_html__( 'Loading map...', 'google-map-multi-marker' ) . '</p>'; // Placeholder text
	$output .= '</div>';

	return $output;
}


/**
 * Register frontend scripts.
 * This ensures WordPress and Elementor know about the scripts.
 */
function gmap_mm_register_frontend_scripts() {
    $api_key = get_option( 'gmap_api_key' );
	if ( empty( $api_key ) ) {
        return;
	}

    if ( ! wp_script_is( 'gmap-mm-frontend-script', 'registered' ) ) {
         wp_register_script(
            'gmap-mm-frontend-script',
            GMAP_MM_PLUGIN_URL . 'assets/js/frontend-map.js',
            array( 'jquery' ),
            GMAP_MM_VERSION,
            true
        );
	}

    if ( ! wp_script_is( 'google-maps-api', 'registered' ) ) {
        wp_register_script(
            'google-maps-api',
            'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&libraries=marker&v=beta',
            array(),
            null,
            true
        );
	}

    if ( ! wp_style_is( 'gmap-mm-frontend-style', 'registered' ) ) {
        wp_register_style(
            'gmap-mm-frontend-style',
            GMAP_MM_PLUGIN_URL . 'assets/css/frontend-map.css',
            array(),
            GMAP_MM_VERSION
        );
	}
}
add_action( 'wp_enqueue_scripts', 'gmap_mm_register_frontend_scripts' );


/**
 * Register and Enqueue scripts specifically for the Elementor Editor.
 */
function gmap_mm_enqueue_editor_scripts() {
    $api_key = get_option( 'gmap_api_key' );
	if ( empty( $api_key ) ) {
        return;
	}

    $editor_api_handle = 'google-maps-api-editor';
    if ( ! wp_script_is( $editor_api_handle, 'registered' ) ) {
         wp_register_script(
            $editor_api_handle,
            'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&libraries=marker&v=beta',
            array(),
            null,
            true
        );
	}

    wp_register_script(
        'gmap-mm-editor-script',
        GMAP_MM_PLUGIN_URL . 'assets/js/google-map-editor.js',
        array( 'jquery', 'elementor-frontend', $editor_api_handle ),
        GMAP_MM_VERSION,
        true
    );

    // ====================================================================================
    // == MODIFICATION START: Localize data to pass the plugin URL to the editor script ==
    // ====================================================================================
    wp_localize_script(
        'gmap-mm-editor-script',
        'gmapMmEditorData', // A unique object name for editor-specific data
        [
            'pluginUrl' => GMAP_MM_PLUGIN_URL
        ]
    );
    // ====================================================================================
    // == MODIFICATION END                                                               ==
    // ====================================================================================

	if ( ! wp_style_is( 'gmap-mm-frontend-style', 'registered' ) ) {
         wp_register_style(
            'gmap-mm-frontend-style',
            GMAP_MM_PLUGIN_URL . 'assets/css/frontend-map.css',
            array(),
            GMAP_MM_VERSION
        );
	}
    wp_enqueue_style( 'gmap-mm-frontend-style' );
}
add_action( 'elementor/preview/enqueue_scripts', 'gmap_mm_enqueue_editor_scripts' );