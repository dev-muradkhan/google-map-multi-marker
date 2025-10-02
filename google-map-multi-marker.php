<?php
/**
 * Plugin Name:       Google Map Multi-Marker
 * Plugin URI:        https://techengi.com/
 * Description:       Displays Google Maps with multiple customizable markers. Manage maps and markers easily. Includes Elementor support.
 * Version:           1.1.0
 * Author:            Md Murad Khan
 * Author URI:        https://techengi.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       google-map-multi-marker
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define constants
 */
define( 'GMAP_MM_VERSION', '1.1.0' );
define( 'GMAP_MM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GMAP_MM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GMAP_MM_DEFAULT_MARKER_ICON', GMAP_MM_PLUGIN_URL . 'assets/images/icon-marker.png' );
define( 'GMAP_MM_DEFAULT_TOOLTIP_IMAGE', GMAP_MM_PLUGIN_URL . 'assets/images/desc-marker.jpg' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
// require plugin_dir_path( __FILE__ ) . 'includes/class-google-map-multi-marker.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_google_map_multi_marker() {

	// $plugin = new Google_Map_Multi_Marker();
	// $plugin->run();

}
// run_google_map_multi_marker();

// Include necessary files
require_once GMAP_MM_PLUGIN_DIR . 'includes/post-types.php';
require_once GMAP_MM_PLUGIN_DIR . 'includes/admin-settings.php';
require_once GMAP_MM_PLUGIN_DIR . 'includes/admin-meta-boxes.php';
require_once GMAP_MM_PLUGIN_DIR . 'includes/admin-columns.php';
require_once GMAP_MM_PLUGIN_DIR . 'includes/shortcode.php';
require_once GMAP_MM_PLUGIN_DIR . 'includes/export-handler.php'; // ** NEW: Include export handler **

// Load AJAX handlers only if doing AJAX
if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
    require_once GMAP_MM_PLUGIN_DIR . 'includes/ajax-handlers.php';
}

/**
 * Allow WebP uploads.
 *
 * @param array $mime_types Allowed mime types.
 * @return array Modified mime types.
 */
function gmap_mm_allow_webp_upload( $mime_types ) {
    // Add webp mime type
    $mime_types['webp'] = 'image/webp';
    return $mime_types;
}
add_filter( 'upload_mimes', 'gmap_mm_allow_webp_upload' );


// --- Elementor Integration ---
/**
 * Include Elementor widget file after Elementor has initialized.
 */
function gmap_mm_register_elementor_widget_on_init() {
    // Check if Elementor is loaded and the Widgets_Manager class exists
    if ( did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Widgets_Manager' ) ) {
        require_once GMAP_MM_PLUGIN_DIR . 'includes/elementor-widget.php';
        // The registration itself happens via the hook inside elementor-widget.php
    }
}
add_action( 'elementor/init', 'gmap_mm_register_elementor_widget_on_init' );

/**
 * Display admin notice for Google Maps API key billing issue.
 */
function gmap_mm_billing_notice() {
    // Only show notice to users who can manage options (e.g., administrators)
    if ( current_user_can( 'manage_options' ) ) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>Google Map Multi-Marker Plugin: Important Notice!</strong>
            </p>
            <p>
                Your Google Maps may not be displaying correctly due to a <strong>"Billing Not Enabled"</strong> error from the Google Maps JavaScript API.
                To resolve this, please ensure that:
            </p>
            <ol>
                <li>You have a valid Google Maps API Key configured.</li>
                <li>Billing is enabled for your Google Cloud Project associated with the API Key.</li>
                <li>The necessary Google Maps APIs (e.g., Maps JavaScript API, Geocoding API, Places API) are enabled in your Google Cloud Console.</li>
            </ol>
            <p>
                For more details, please refer to the Google Maps Platform documentation on <a href="https://developers.google.com/maps/documentation/javascript/error-messages#billing-not-enabled-map-error" target="_blank">Billing Not Enabled Map Error</a>.
            </p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'gmap_mm_billing_notice' );

// Placeholder for future includes and activation/deactivation hooks

// Closing PHP tag omitted intentionally
