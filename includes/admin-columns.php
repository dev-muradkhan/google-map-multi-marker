<?php
/**
 * Customizes the Admin Columns for the gmap_map Custom Post Type list table.
 *
 * @package GoogleMapMultiMarker
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Add custom columns to the gmap_map post type list table.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function gmap_mm_add_map_columns( $columns ) {
    // Add Shortcode column before the Date column
    $new_columns = [];
    foreach ( $columns as $key => $title ) {
        if ( $key === 'date' ) {
            $new_columns['shortcode'] = __( 'Shortcode', 'google-map-multi-marker' );
        }
        $new_columns[ $key ] = $title;
    }
    // If 'date' column wasn't found (unlikely), append at the end.
    if ( ! isset( $new_columns['shortcode'] ) ) {
         $new_columns['shortcode'] = __( 'Shortcode', 'google-map-multi-marker' );
    }

    return $new_columns;
}
add_filter( 'manage_gmap_map_posts_columns', 'gmap_mm_add_map_columns' );

/**
 * Render the content for the custom columns.
 *
 * @param string $column_name The name of the column to display.
 * @param int    $post_id     The current post ID.
 */
function gmap_mm_render_map_columns( $column_name, $post_id ) {
    if ( 'shortcode' === $column_name ) {
        $shortcode = '[map-multi-marker id="' . $post_id . '"]';
        ?>
        <input type="text" readonly="readonly" value="<?php echo esc_attr( $shortcode ); ?>" class="widefat gmap-mm-shortcode-input" />
        <button type="button" class="button button-small gmap-mm-copy-shortcode-list" data-shortcode="<?php echo esc_attr($shortcode); ?>" style="margin-top: 3px;"><?php esc_html_e( 'Copy', 'google-map-multi-marker' ); ?></button>
        <span class="gmap-mm-copy-feedback-list" style="margin-left: 5px; color: green; display: none;"><?php esc_html_e( 'Copied!', 'google-map-multi-marker' ); ?></span>
        <?php
    }
}
add_action( 'manage_gmap_map_posts_custom_column', 'gmap_mm_render_map_columns', 10, 2 );

/**
 * Enqueue script for the copy button in the admin list table.
 */
function gmap_mm_enqueue_admin_list_scripts( $hook ) {
    global $post_type;

    // Only load on the edit screen for our CPT list table
    if ( 'edit.php' === $hook && isset($_GET['post_type']) && 'gmap_map' === $_GET['post_type'] ) {
        wp_add_inline_script( 'jquery', "
            jQuery(document).ready(function($){
                $('body').on('click', '.gmap-mm-copy-shortcode-list', function() {
                    var shortcode = $(this).data('shortcode');
                    var tempInput = $('<input>');
                    $('body').append(tempInput);
                    tempInput.val(shortcode).select();
                    document.execCommand('copy');
                    tempInput.remove();
                    var feedback = $(this).next('.gmap-mm-copy-feedback-list');
                    feedback.fadeIn().delay(1500).fadeOut();
                });
                // Make input selectable on click
                 $('body').on('click', '.gmap-mm-shortcode-input', function() {
                    $(this).select();
                 });
            });
        ");
    }
}
add_action( 'admin_enqueue_scripts', 'gmap_mm_enqueue_admin_list_scripts' );

?>
