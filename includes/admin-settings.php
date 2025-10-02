<?php
/**
 * Handles the Admin Settings page for the Google Map Multi-Marker plugin.
 *
 * @package GoogleMapMultiMarker
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Add the settings page to the admin menu.
 */
function gmap_mm_add_settings_page() {
	add_submenu_page(
		'edit.php?post_type=gmap_map', // Parent slug (under the CPT menu)
		__( 'API Settings', 'google-map-multi-marker' ), // Page title
		__( 'API Settings', 'google-map-multi-marker' ), // Menu title
		'manage_options', // Capability required
		'gmap-mm-settings', // Menu slug
		'gmap_mm_render_settings_page' // Callback function to render the page
	);
}
add_action( 'admin_menu', 'gmap_mm_add_settings_page' );

/**
 * Register the settings fields.
 */
function gmap_mm_register_settings() {
	register_setting(
		'gmap_mm_options_group', // Option group
		'gmap_api_key', // Option name
		'sanitize_text_field' // Sanitize callback
	);

    register_setting(
        'gmap_mm_options_group', // Option group
        'gmap_mm_default_marker_icon', // Option name
        'esc_url_raw' // Sanitize callback for URL
    );

    register_setting(
        'gmap_mm_options_group', // Option group
        'gmap_mm_default_tooltip_image', // Option name
        'esc_url_raw' // Sanitize callback for URL
    );

	add_settings_section(
		'gmap_mm_api_section', // ID
		__( 'Google Maps API Key', 'google-map-multi-marker' ), // Title
		'gmap_mm_api_section_callback', // Callback
		'gmap-mm-settings' // Page slug
	);

	add_settings_field(
		'gmap_api_key_field', // ID
		__( 'API Key', 'google-map-multi-marker' ), // Title
		'gmap_mm_api_key_field_callback', // Callback
		'gmap-mm-settings', // Page slug
		'gmap_mm_api_section' // Section ID
	);

    add_settings_section(
        'gmap_mm_default_images_section', // ID
        __( 'Default Marker & Tooltip Images', 'google-map-multi-marker' ), // Title
        'gmap_mm_default_images_section_callback', // Callback
        'gmap-mm-settings' // Page slug
    );

    add_settings_field(
        'gmap_mm_default_marker_icon_field', // ID
        __( 'Default marker icon when a new marker is created', 'google-map-multi-marker' ), // Title
        'gmap_mm_default_marker_icon_field_callback', // Callback
        'gmap-mm-settings', // Page slug
        'gmap_mm_default_images_section' // Section ID
    );

    add_settings_field(
        'gmap_mm_default_tooltip_image_field', // ID
        __( 'Default image tooltip when a new marker is created', 'google-map-multi-marker' ), // Title
        'gmap_mm_default_tooltip_image_field_callback', // Callback
        'gmap-mm-settings', // Page slug
        'gmap_mm_default_images_section' // Section ID
    );
}
add_action( 'admin_init', 'gmap_mm_register_settings' );

/**
 * Enqueue media uploader scripts and styles.
 *
 * @param string $hook The current admin page hook.
 */
function gmap_mm_enqueue_media_uploader_scripts( $hook ) {
    if ( 'gmap_map_page_gmap-mm-settings' !== $hook ) { // Adjust hook name if necessary based on actual page hook
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script( 'gmap-mm-media-uploader', GMAP_MM_PLUGIN_URL . 'assets/js/admin-media-uploader.js', array( 'jquery' ), GMAP_MM_VERSION, true );
    wp_localize_script( 'gmap-mm-media-uploader', 'gmap_mm_media_uploader', array(
        'title' => __( 'Select Image', 'google-map-multi-marker' ),
        'button' => __( 'Use this image', 'google-map-multi-marker' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'gmap_mm_enqueue_media_uploader_scripts' );


/**
 * Callback for the API settings section.
 */
function gmap_mm_api_section_callback() {
	echo '<p>' . esc_html__( 'Enter your Google Maps JavaScript API key below. You can obtain one from the Google Cloud Console.', 'google-map-multi-marker' ) . '</p>';
    // Optional: Add a link to Google Cloud Console documentation.
}

/**
 * Callback to render the API key input field.
 */
function gmap_mm_api_key_field_callback() {
	$api_key = get_option( 'gmap_api_key' );
	echo '<input type="text" id="gmap_api_key" name="gmap_api_key" value="' . esc_attr( $api_key ) . '" size="50" />';
    // Optional: Add an API key validation/test button here later.
}

/**
 * Callback for the Default Images settings section.
 */
function gmap_mm_default_images_section_callback() {
    echo '<p>' . esc_html__( 'Set default images for new markers. These can be overridden for individual markers.', 'google-map-multi-marker' ) . '</p>';
}

/**
 * Callback to render the Default Marker Icon input field.
 */
function gmap_mm_default_marker_icon_field_callback() {
    $default_icon = get_option( 'gmap_mm_default_marker_icon', GMAP_MM_DEFAULT_MARKER_ICON );
    $is_image_set = ! empty( $default_icon );
    ?>
    <div class="gmap-mm-image-upload-field">
        <img src="<?php echo esc_url( $default_icon ); ?>" class="gmap-mm-image-preview" style="max-width: 100px; height: auto; display: <?php echo $is_image_set ? 'block' : 'none'; ?>; margin-bottom: 10px;" />
        <input type="hidden" name="gmap_mm_default_marker_icon" value="<?php echo esc_attr( $default_icon ); ?>" class="gmap-mm-image-url" />
        <button type="button" class="button gmap-mm-upload-button"><?php esc_html_e( 'Select Image', 'google-map-multi-marker' ); ?></button>
        <button type="button" class="button gmap-mm-remove-button" style="display: <?php echo $is_image_set ? 'inline-block' : 'none'; ?>;"><?php esc_html_e( 'Remove Image', 'google-map-multi-marker' ); ?></button>
    </div>
    <?php
}

/**
 * Callback to render the Default Tooltip Image input field.
 */
function gmap_mm_default_tooltip_image_field_callback() {
    $default_tooltip = get_option( 'gmap_mm_default_tooltip_image', GMAP_MM_DEFAULT_TOOLTIP_IMAGE );
    $is_image_set = ! empty( $default_tooltip );
    ?>
    <div class="gmap-mm-image-upload-field">
        <img src="<?php echo esc_url( $default_tooltip ); ?>" class="gmap-mm-image-preview" style="max-width: 100px; height: auto; display: <?php echo $is_image_set ? 'block' : 'none'; ?>; margin-bottom: 10px;" />
        <input type="hidden" name="gmap_mm_default_tooltip_image" value="<?php echo esc_attr( $default_tooltip ); ?>" class="gmap-mm-image-url" />
        <button type="button" class="button gmap-mm-upload-button"><?php esc_html_e( 'Select Image', 'google-map-multi-marker' ); ?></button>
        <button type="button" class="button gmap-mm-remove-button" style="display: <?php echo $is_image_set ? 'inline-block' : 'none'; ?>;"><?php esc_html_e( 'Remove Image', 'google-map-multi-marker' ); ?></button>
    </div>
    <?php
}

/**
 * Render the settings page HTML.
 */
function gmap_mm_render_settings_page() {
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'gmap_mm_options_group' ); // Output nonce, action, and option_page fields for the group.
			do_settings_sections( 'gmap-mm-settings' ); // Output the sections and fields for the page slug.
			submit_button();
			?>
		</form>
	</div>
	<?php
}
?>
