<?php
/**
 * Handles Meta Boxes for the gmap_map Custom Post Type.
 *
 * @package GoogleMapMultiMarker
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Add meta boxes to the CPT edit screen.
 */
function gmap_mm_add_meta_boxes() {
	add_meta_box(
		'gmap_mm_map_settings_meta_box', // ID
		__( 'Map Settings', 'google-map-multi-marker' ), // Title
		'gmap_mm_render_map_settings_meta_box', // Callback function
		'gmap_map', // Post type
		'normal', // Context (normal, side, advanced)
		'high' // Priority (high, core, default, low)
	);

    add_meta_box(
		'gmap_mm_markers_meta_box', // ID
		__( 'Manage Markers', 'google-map-multi-marker' ), // Title
		'gmap_mm_render_markers_meta_box', // Callback function
		'gmap_map', // Post type
		'normal', // Context
		'high' // Priority
	);

    add_meta_box(
		'gmap_mm_shortcode_meta_box', // ID
		__( 'Shortcode', 'google-map-multi-marker' ), // Title
		'gmap_mm_render_shortcode_meta_box', // Callback function
		'gmap_map', // Post type
		'side', // Context
		'default' // Priority
	);
}
add_action( 'add_meta_boxes_gmap_map', 'gmap_mm_add_meta_boxes' ); // Hook specifically for our CPT

/**
 * Render the Map Settings meta box content.
 *
 * @param WP_Post $post The post object.
 */
function gmap_mm_render_map_settings_meta_box( $post ) {
	// Add a nonce field for security
	wp_nonce_field( 'gmap_mm_save_map_settings', 'gmap_mm_map_settings_nonce' );

	// Get saved meta values
	$options = get_post_meta( $post->ID, '_gmap_options', true );
    $options = is_array($options) ? $options : []; // Ensure it's an array

    // Default values
    $defaults = [
        'width' => '100%',
        'height' => '400px',
        'zoom' => 8,
        'lat' => '39.8283', // Center of US approx.
        'lng' => '-98.5795',
        'map_type' => 'roadmap', // roadmap, satellite, hybrid, terrain
        'tooltip_show_title' => '1',
        'tooltip_show_address' => '1',
        'tooltip_show_phone' => '1',
        'tooltip_show_weblink' => '1',
        'tooltip_show_image' => '1',
        'custom_styles' => '',
    ];

    $settings = wp_parse_args($options, $defaults);

	// Parse width and height values for the new input fields
	// Width
	preg_match('/^(\d*\.?\d+)(\S*)$/', $settings['width'], $width_parts);
	$width_val = !empty($width_parts[1]) ? $width_parts[1] : 100;
	$width_unit = !empty($width_parts[2]) ? $width_parts[2] : '%';

	// Height
	preg_match('/^(\d*\.?\d+)(\S*)$/', $settings['height'], $height_parts);
	$height_val = !empty($height_parts[1]) ? $height_parts[1] : 400;
	$height_unit = !empty($height_parts[2]) ? $height_parts[2] : 'px';


	?>
	<table class="form-table">
		<tbody>
			<!-- Width -->
			<tr>
				<th scope="row">
                    <label for="gmap_mm_width_val"><?php esc_html_e( 'Map Width', 'google-map-multi-marker' ); ?></label>
                </th>
				<td>
					<?php // ** UPDATED: New width inputs ** ?>
                    <input type="number" id="gmap_mm_width_val" name="_gmap_options[width_value]" value="<?php echo esc_attr( $width_val ); ?>" class="small-text" min="0" step="1" />
					<select name="_gmap_options[width_unit]">
						<option value="px" <?php selected($width_unit, 'px'); ?>>px</option>
						<option value="%" <?php selected($width_unit, '%'); ?>>%</option>
					</select>
                    <p class="description"><?php esc_html_e( 'Enter width value and select unit. Default: 100%', 'google-map-multi-marker' ); ?></p>
                </td>
			</tr>
            <!-- Height -->
            <tr>
				<th scope="row">
                    <label for="gmap_mm_height_val"><?php esc_html_e( 'Map Height', 'google-map-multi-marker' ); ?></label>
                </th>
				<td>
					<?php // ** UPDATED: New height inputs ** ?>
                    <input type="number" id="gmap_mm_height_val" name="_gmap_options[height_value]" value="<?php echo esc_attr( $height_val ); ?>" class="small-text" min="0" step="1" />
					<select name="_gmap_options[height_unit]">
						<option value="px" <?php selected($height_unit, 'px'); ?>>px</option>
						<option value="%" <?php selected($height_unit, '%'); ?>>%</option>
					</select>
                     <p class="description"><?php esc_html_e( 'Enter height value and select unit. Default: 400px', 'google-map-multi-marker' ); ?></p>
                </td>
			</tr>
            <!-- Zoom Level -->
            <tr>
				<th scope="row">
                    <label for="gmap_mm_zoom"><?php esc_html_e( 'Default Zoom Level', 'google-map-multi-marker' ); ?></label>
                </th>
				<td>
                    <input type="number" id="gmap_mm_zoom" name="_gmap_options[zoom]" value="<?php echo esc_attr( $settings['zoom'] ); ?>" min="1" max="22" step="1" />
                    <p class="description"><?php esc_html_e( 'Zoom level from 1 (world) to 22 (building). Default: 8', 'google-map-multi-marker' ); ?></p>
                </td>
			</tr>
            <!-- Center Latitude -->
            <tr>
				<th scope="row">
                    <label for="gmap_mm_lat"><?php esc_html_e( 'Center Latitude', 'google-map-multi-marker' ); ?></label>
                </th>
				<td>
                    <input type="text" id="gmap_mm_lat" name="_gmap_options[lat]" value="<?php echo esc_attr( $settings['lat'] ); ?>" size="15" />
                     <p class="description"><?php esc_html_e( 'Default map center latitude. Default: 39.8283', 'google-map-multi-marker' ); ?></p>
                </td>
			</tr>
             <!-- Center Longitude -->
            <tr>
				<th scope="row">
                    <label for="gmap_mm_lng"><?php esc_html_e( 'Center Longitude', 'google-map-multi-marker' ); ?></label>
                </th>
				<td>
                    <input type="text" id="gmap_mm_lng" name="_gmap_options[lng]" value="<?php echo esc_attr( $settings['lng'] ); ?>" size="15" />
                     <p class="description"><?php esc_html_e( 'Default map center longitude. Default: -98.5795', 'google-map-multi-marker' ); ?></p>
                </td>
			</tr>
            <!-- Map Type -->
            <tr>
				<th scope="row">
                    <label for="gmap_mm_map_type"><?php esc_html_e( 'Map Type', 'google-map-multi-marker' ); ?></label>
                </th>
				<td>
                    <select id="gmap_mm_map_type" name="_gmap_options[map_type]">
                        <option value="roadmap" <?php selected( $settings['map_type'], 'roadmap' ); ?>><?php esc_html_e( 'Roadmap', 'google-map-multi-marker' ); ?></option>
                        <option value="satellite" <?php selected( $settings['map_type'], 'satellite' ); ?>><?php esc_html_e( 'Satellite', 'google-map-multi-marker' ); ?></option>
                        <option value="hybrid" <?php selected( $settings['map_type'], 'hybrid' ); ?>><?php esc_html_e( 'Hybrid', 'google-map-multi-marker' ); ?></option>
                        <option value="terrain" <?php selected( $settings['map_type'], 'terrain' ); ?>><?php esc_html_e( 'Terrain', 'google-map-multi-marker' ); ?></option>
                    </select>
                     <p class="description"><?php esc_html_e( 'Default map display type.', 'google-map-multi-marker' ); ?></p>
                </td>
			</tr>
            <!-- Tooltip Options -->
            <tr>
                <th scope="row"><?php esc_html_e( 'Tooltip Fields', 'google-map-multi-marker' ); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php esc_html_e( 'Show fields in marker tooltips', 'google-map-multi-marker' ); ?></span></legend>
                        <label><input type="checkbox" name="_gmap_options[tooltip_show_title]" value="1" <?php checked( $settings['tooltip_show_title'], '1' ); ?> /> <?php esc_html_e( 'Show Title', 'google-map-multi-marker' ); ?></label><br>
                        <label><input type="checkbox" name="_gmap_options[tooltip_show_address]" value="1" <?php checked( $settings['tooltip_show_address'], '1' ); ?> /> <?php esc_html_e( 'Show Address', 'google-map-multi-marker' ); ?></label><br>
                        <label><input type="checkbox" name="_gmap_options[tooltip_show_phone]" value="1" <?php checked( $settings['tooltip_show_phone'], '1' ); ?> /> <?php esc_html_e( 'Show Phone', 'google-map-multi-marker' ); ?></label><br>
                        <label><input type="checkbox" name="_gmap_options[tooltip_show_weblink]" value="1" <?php checked( $settings['tooltip_show_weblink'], '1' ); ?> /> <?php esc_html_e( 'Show Web Link', 'google-map-multi-marker' ); ?></label><br>
                        <label><input type="checkbox" name="_gmap_options[tooltip_show_image]" value="1" <?php checked( $settings['tooltip_show_image'], '1' ); ?> /> <?php esc_html_e( 'Show Image', 'google-map-multi-marker' ); ?></label><br>
                    </fieldset>
                    <p class="description"><?php esc_html_e( 'Select which fields to display in the marker info window (tooltip).', 'google-map-multi-marker' ); ?></p>
                </td>
            </tr>
            <!-- Custom Map Styles -->
            <tr>
                <th scope="row">
                    <label for="gmap_mm_custom_styles"><?php esc_html_e( 'Custom Map Styles (JSON)', 'google-map-multi-marker' ); ?></label>
                </th>
                <td>
                    <textarea id="gmap_mm_custom_styles" name="_gmap_options[custom_styles]" rows="10" cols="60" class="large-text code"><?php echo esc_textarea( $settings['custom_styles'] ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Optional. Enter a JSON array for custom Google Map styling. This will override the default map type appearance. You can generate styles from tools like Snazzy Maps.', 'google-map-multi-marker' ); ?></p>
                </td>
            </tr>
		</tbody>
	</table>
    <?php
    // We will add JS for the media uploader later
}

/**
 * Render the Manage Markers meta box content.
 * Placeholder for now - will be implemented in User Story 1.2.
 *
 * @param WP_Post $post The post object.
 */
function gmap_mm_render_markers_meta_box( $post ) {
    // Add nonce fields for security
	wp_nonce_field( 'gmap_mm_save_markers', 'gmap_mm_markers_nonce' ); // For add/edit/delete
    wp_nonce_field( 'gmap_mm_import_markers', 'gmap_mm_import_markers_nonce' ); // For import

    // Get saved markers
    $markers = get_post_meta( $post->ID, '_gmap_markers', true );
    $markers = is_array($markers) ? $markers : [];
    $has_markers = ! empty($markers);

    // Get global default marker and tooltip images
    $default_marker_icon = get_option( 'gmap_mm_default_marker_icon', GMAP_MM_DEFAULT_MARKER_ICON );
    $default_tooltip_image = get_option( 'gmap_mm_default_tooltip_image', GMAP_MM_DEFAULT_TOOLTIP_IMAGE );

    // ** NEW: Create the URL for the export button **
    $export_nonce = wp_create_nonce('gmap_mm_export_csv_nonce');
    $export_url = admin_url('admin.php?action=gmap_mm_export_csv&post_id=' . $post->ID . '&gmap_mm_nonce=' . $export_nonce);

    ?>
    <div id="gmap-markers-container" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
        <p><?php esc_html_e( 'Manage markers for this map below. Click "Add Marker" to add a new location, or use the import feature.', 'google-map-multi-marker' ); ?></p>

        <table class="wp-list-table widefat fixed striped" id="gmap-markers-table">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e( 'Title', 'google-map-multi-marker' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Address', 'google-map-multi-marker' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Latitude', 'google-map-multi-marker' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Longitude', 'google-map-multi-marker' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Phone', 'google-map-multi-marker' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Web Link', 'google-map-multi-marker' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Marker Image', 'google-map-multi-marker' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Tooltip Image', 'google-map-multi-marker' ); ?></th>
                    <th scope="col" style="width: 100px;"><?php esc_html_e( 'Actions', 'google-map-multi-marker' ); ?></th>
                </tr>
            </thead>
            <tbody id="gmap-markers-list">
                <?php // Marker rows will be populated by JavaScript ?>
            </tbody>
             <tfoot>
                <tr>
                    <td colspan="9">
                        <button type="button" class="button button-primary" id="gmap-mm-add-marker-button"><?php esc_html_e( 'Add Marker', 'google-map-multi-marker' ); ?></button>
                        <?php // ** NEW: Added Export Button ** ?>
                        <?php if ($has_markers) : ?>
                            <a href="<?php echo esc_url($export_url); ?>" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Export Markers (CSV)', 'google-map-multi-marker' ); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div id="gmap-marker-import-export-section" style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;">
            <h3><?php esc_html_e( 'Bulk Import Markers (CSV)', 'google-map-multi-marker' ); ?></h3>
            <p><?php esc_html_e( 'Upload a CSV file with marker data. Required columns: latitude, longitude. Optional columns: title, address, phone, web_link, marker_image, tooltip_image.', 'google-map-multi-marker' ); ?></p>
            <input type="file" id="gmap-mm-csv-file" name="csv_file" accept=".csv" />
            <button type="button" class="button" id="gmap-mm-import-csv-button"><?php esc_html_e( 'Import CSV', 'google-map-multi-marker' ); ?></button>
            <span class="spinner" style="float: none; vertical-align: middle;"></span>
            <p id="gmap-mm-import-status" style="display: inline-block; margin-left: 10px;"></p>
        </div>

        <?php // Hidden template row for adding new markers ?>
        <table style="display: none;">
            <tr id="gmap-marker-template-row" class="gmap-marker-row editable" data-marker-id="">
                <td><input type="text" name="title" placeholder="<?php esc_attr_e( 'Title', 'google-map-multi-marker' ); ?>" /></td>
                <td><input type="text" name="address" placeholder="<?php esc_attr_e( 'Address', 'google-map-multi-marker' ); ?>" /></td>
                <td><input type="text" name="latitude" placeholder="<?php esc_attr_e( 'Latitude', 'google-map-multi-marker' ); ?>" /></td>
                <td><input type="text" name="longitude" placeholder="<?php esc_attr_e( 'Longitude', 'google-map-multi-marker' ); ?>" /></td>
                <td><input type="text" name="phone" placeholder="<?php esc_attr_e( 'Phone', 'google-map-multi-marker' ); ?>" /></td>
                <td><input type="text" name="web_link" placeholder="<?php esc_attr_e( 'https://example.com', 'google-map-multi-marker' ); ?>" /></td>
                <td>
                    <?php $is_marker_image_set = ! empty( $default_marker_icon ); ?>
                    <div class="gmap-mm-image-upload-field">
                        <img src="<?php echo esc_url( $default_marker_icon ); ?>" class="gmap-mm-image-preview" style="max-width: 50px; height: auto; display: <?php echo $is_marker_image_set ? 'block' : 'none'; ?>; margin-bottom: 5px;" />
                        <input type="hidden" name="marker_image" value="<?php echo esc_attr( $default_marker_icon ); ?>" class="gmap-mm-image-url" />
                        <button type="button" class="button button-small gmap-mm-upload-button"><?php esc_html_e( 'Select', 'google-map-multi-marker' ); ?></button>
                        <button type="button" class="button button-small gmap-mm-remove-button" style="display: <?php echo $is_marker_image_set ? 'inline-block' : 'none'; ?>;"><?php esc_html_e( 'Remove', 'google-map-multi-marker' ); ?></button>
                    </div>
                </td>
                <td>
                    <?php $is_tooltip_image_set = ! empty( $default_tooltip_image ); ?>
                    <div class="gmap-mm-image-upload-field">
                        <img src="<?php echo esc_url( $default_tooltip_image ); ?>" class="gmap-mm-image-preview" style="max-width: 50px; height: auto; display: <?php echo $is_tooltip_image_set ? 'block' : 'none'; ?>; margin-bottom: 5px;" />
                        <input type="hidden" name="tooltip_image" value="<?php echo esc_attr( $default_tooltip_image ); ?>" class="gmap-mm-image-url" />
                        <button type="button" class="button button-small gmap-mm-upload-button"><?php esc_html_e( 'Select', 'google-map-multi-marker' ); ?></button>
                        <button type="button" class="button button-small gmap-mm-remove-button" style="display: <?php echo $is_tooltip_image_set ? 'inline-block' : 'none'; ?>;"><?php esc_html_e( 'Remove', 'google-map-multi-marker' ); ?></button>
                    </div>
                </td>
                <td class="actions">
                    <button type="button" class="button button-primary gmap-mm-save-marker" title="<?php esc_attr_e( 'Save Marker', 'google-map-multi-marker' ); ?>">✅</button>
                    <button type="button" class="button gmap-mm-cancel-edit" title="<?php esc_attr_e( 'Cancel Edit', 'google-map-multi-marker' ); ?>" style="display: none;">❌</button>
                    <button type="button" class="button gmap-mm-edit-marker" title="<?php esc_attr_e( 'Edit Marker', 'google-map-multi-marker' ); ?>" style="display: none;">✏️</button>
                    <button type="button" class="button button-link-delete gmap-mm-delete-marker" title="<?php esc_attr_e( 'Delete Marker', 'google-map-multi-marker' ); ?>" style="display: none;">🗑️</button>
                    <span class="spinner"></span>
                </td>
            </tr>
        </table>

        <?php // Store initial markers data for JS ?>
        <script type="text/javascript">
            var gmapMmInitialMarkers = <?php echo wp_json_encode( array_values($markers) ); // Use array_values to ensure it's a JSON array ?>;
            // Pass AJAX URL and nonces to JavaScript
            var gmapMmAjax = {
                ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                saveNonce: '<?php echo esc_js( wp_create_nonce( 'gmap_mm_save_markers' ) ); ?>',
                importNonce: '<?php echo esc_js( wp_create_nonce( 'gmap_mm_import_markers' ) ); ?>',
                defaultMarkerIcon: '<?php echo esc_url( $default_marker_icon ); ?>',
                defaultTooltipImage: '<?php echo esc_url( $default_tooltip_image ); ?>'
            };
        </script>
    </div>
    <?php
    // We will add JS/CSS for the dynamic table later.
}

/**
 * Render the Shortcode meta box content.
 *
 * @param WP_Post $post The post object.
 */
function gmap_mm_render_shortcode_meta_box( $post ) {
    if ( $post->post_status === 'publish' || $post->post_status === 'private' ) {
        $shortcode = '[map-multi-marker id="' . $post->ID . '"]';
        ?>
        <p><?php esc_html_e( 'Copy this shortcode and paste it into your post, page, or text widget content:', 'google-map-multi-marker' ); ?></p>
        <span style="border: 1px solid #ccc; background-color: #f9f9f9; padding: 5px 8px; display: inline-block; font-family: monospace;">
            <?php echo esc_html( $shortcode ); ?>
        </span>
        <button type="button" class="button button-small" id="gmap_mm_copy_shortcode" data-shortcode="<?php echo esc_attr($shortcode); ?>" style="margin-left: 5px;"><?php esc_html_e( 'Copy', 'google-map-multi-marker' ); ?></button>
        <span id="gmap_mm_copy_feedback" style="margin-left: 5px; color: green; display: none;"><?php esc_html_e( 'Copied!', 'google-map-multi-marker' ); ?></span>
        <script type="text/javascript">
            jQuery(document).ready(function($){
                $('#gmap_mm_copy_shortcode').on('click', function() {
                    var shortcode = $(this).data('shortcode');
                    var tempInput = $('<input>');
                    $('body').append(tempInput);
                    tempInput.val(shortcode).select();
                    document.execCommand('copy');
                    tempInput.remove();
                    $('#gmap_mm_copy_feedback').fadeIn().delay(1500).fadeOut();
                });
            });
        </script>
        <?php
    } else {
        ?>
        <p><?php esc_html_e( 'Publish this map to generate the shortcode.', 'google-map-multi-marker' ); ?></p>
        <?php
    }
}


/**
 * Save meta box data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function gmap_mm_save_meta_data( $post_id ) {
	// Check if our nonce is set.
	if ( ! isset( $_POST['gmap_mm_map_settings_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['gmap_mm_map_settings_nonce'], 'gmap_mm_save_map_settings' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'gmap_map' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	} else {
        // This check might be redundant given the add_meta_boxes hook used, but good practice.
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
	}

	/* OK, it's safe for us to save the data now. */

    // --- Save Map Settings ---
	if ( isset( $_POST['_gmap_options'] ) && is_array( $_POST['_gmap_options'] ) ) {
        $map_options_data = $_POST['_gmap_options'];
        $sanitized_options = [];

		// ** UPDATED: Sanitize and combine new width/height fields **
		$width_val = isset($map_options_data['width_value']) ? absint($map_options_data['width_value']) : 100;
		$width_unit = isset($map_options_data['width_unit']) && in_array($map_options_data['width_unit'], ['px', '%']) ? $map_options_data['width_unit'] : '%';
		$sanitized_options['width'] = $width_val . $width_unit;

		$height_val = isset($map_options_data['height_value']) ? absint($map_options_data['height_value']) : 400;
		$height_unit = isset($map_options_data['height_unit']) && in_array($map_options_data['height_unit'], ['px', '%']) ? $map_options_data['height_unit'] : 'px';
		$sanitized_options['height'] = $height_val . $height_unit;


        // Sanitize each field individually
        
        $sanitized_options['zoom'] = isset($map_options_data['zoom']) ? absint( $map_options_data['zoom'] ) : 8;
        $sanitized_options['lat'] = isset($map_options_data['lat']) ? sanitize_text_field( $map_options_data['lat'] ) : '39.8283';
        $sanitized_options['lng'] = isset($map_options_data['lng']) ? sanitize_text_field( $map_options_data['lng'] ) : '-98.5795';
        $allowed_map_types = ['roadmap', 'satellite', 'hybrid', 'terrain'];
        $sanitized_options['map_type'] = isset($map_options_data['map_type']) && in_array($map_options_data['map_type'], $allowed_map_types) ? $map_options_data['map_type'] : 'roadmap';

        // Sanitize checkbox values (present if checked, absent if not)
        $sanitized_options['tooltip_show_title'] = isset( $map_options_data['tooltip_show_title'] ) ? '1' : '0';
        $sanitized_options['tooltip_show_address'] = isset( $map_options_data['tooltip_show_address'] ) ? '1' : '0';
        $sanitized_options['tooltip_show_phone'] = isset( $map_options_data['tooltip_show_phone'] ) ? '1' : '0';
        $sanitized_options['tooltip_show_weblink'] = isset( $map_options_data['tooltip_show_weblink'] ) ? '1' : '0';
        $sanitized_options['tooltip_show_image'] = isset( $map_options_data['tooltip_show_image'] ) ? '1' : '0';

        // Sanitize custom_styles: ensure it's valid JSON
        $custom_styles_raw = isset( $map_options_data['custom_styles'] ) ? wp_unslash( $map_options_data['custom_styles'] ) : '';
        $decoded_styles = json_decode( $custom_styles_raw );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_styles ) ) {
            // If valid JSON array, re-encode to ensure consistent formatting and store
            $sanitized_options['custom_styles'] = wp_json_encode( $decoded_styles );
        } else {
            // If not valid JSON or not an array, store empty string
            $sanitized_options['custom_styles'] = '';
        }

		// Update the meta field in the database.
		update_post_meta( $post_id, '_gmap_options', $sanitized_options );
	}
    // Note: Removed the 'else { delete_post_meta(...) }' block here.
    // If _gmap_options isn't set in $_POST (which shouldn't happen with the text fields present),
    // we simply won't update the meta, preserving any existing value, which is safer.

    // --- Save Markers (handled by AJAX, but might need a placeholder save if not using AJAX initially) ---
    // Note: Marker saving will primarily be handled via AJAX calls later (User Story 1.2).
    // If we needed basic non-AJAX saving, we'd process `$_POST['_gmap_markers_data_placeholder']` here after JSON decoding and sanitizing.
    // For now, we leave this part out as AJAX is the requirement.

}
add_action( 'save_post_gmap_map', 'gmap_mm_save_meta_data' ); // Hook specifically for our CPT


/**
 * Enqueue scripts and styles for the admin meta boxes.
 */
function gmap_mm_enqueue_admin_scripts( $hook ) {
    global $post;

    // Only load on the edit screen for our CPT
    if ( ( 'post.php' == $hook || 'post-new.php' == $hook ) && isset($post->post_type) && 'gmap_map' == $post->post_type ) {
        // Enqueue WordPress media uploader scripts
        wp_enqueue_media();

        // Enqueue custom script for meta box interactions (including marker table)
        wp_enqueue_script(
            'gmap-mm-admin-meta-box-script',
            GMAP_MM_PLUGIN_URL . 'assets/js/admin-meta-boxes.js',
            ['jquery'],
            GMAP_MM_VERSION,
            true // Load in footer
        );

        // Enqueue admin styles for the marker table
         wp_enqueue_style(
            'gmap-mm-admin-style',
            GMAP_MM_PLUGIN_URL . 'assets/css/admin-styles.css',
            array(),
            GMAP_MM_VERSION
        );

        // Localize data needed for the script (already done inside render function, but could be moved here)
        $markers = get_post_meta( $post->ID, '_gmap_markers', true );
        $markers = is_array($markers) ? $markers : [];
        wp_localize_script('gmap-mm-admin-meta-box-script', 'gmapMmInitialMarkers', array_values($markers));
        wp_localize_script('gmap-mm-admin-meta-box-script', 'gmapMmAjax', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'saveNonce' => wp_create_nonce( 'gmap_mm_save_markers' ),
            'importNonce' => wp_create_nonce( 'gmap_mm_import_markers' ),
            'defaultMarkerIcon' => get_option( 'gmap_mm_default_marker_icon', GMAP_MM_DEFAULT_MARKER_ICON ),
            'defaultTooltipImage' => get_option( 'gmap_mm_default_tooltip_image', GMAP_MM_DEFAULT_TOOLTIP_IMAGE )
        ]);
    }
}
add_action( 'admin_enqueue_scripts', 'gmap_mm_enqueue_admin_scripts' );

// Closing PHP tag omitted intentionally
