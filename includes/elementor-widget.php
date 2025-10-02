<?php
/**
 * Elementor Widget for Google Map Multi Marker.
 *
 * @package GoogleMapMultiMarker
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Note: The check for Elementor is now done in the main plugin file
// before this file is included via the 'plugins_loaded' action.

class Google_Map_Multi_Marker_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'google-map-multi-marker';
	}

	/**
	 * Get widget title.
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Multi-Marker Map', 'google-map-multi-marker' );
	}

	/**
	 * Get widget icon.
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-google-maps'; // Use a relevant Elementor icon
	}

	/**
	 * Get widget categories.
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'general' ]; // Add to the 'General' category in Elementor
	}

    /**
	 * Get widget keywords.
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [ 'map', 'google', 'marker', 'location', 'gmap' ];
	}

    /**
	 * Get script dependencies.
	 * Tells Elementor which scripts are needed for this widget's preview.
	 * @return array List of script handles.
	 */
	public function get_script_depends() {
		// Depend on the editor script and the unique editor Google Maps API handle.
		// These are enqueued via the elementor/editor/before_enqueue_scripts hook in shortcode.php.
		return [ 'gmap-mm-editor-script', 'google-maps-api-editor' ];
	}

    /**
	 * Get style dependencies.
	 * Tells Elementor which styles are needed for this widget's preview.
	 * @return array List of style handles.
	 */
    public function get_style_depends() {
        // This style is registered in shortcode.php via wp_enqueue_scripts
        return [ 'gmap-mm-frontend-style' ];
    }


	/**
	 * Register widget controls.
	 */
	protected function _register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Map Selection', 'google-map-multi-marker' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

        // Get available maps (CPT 'gmap_map')
        $maps_query = new WP_Query( array(
            'post_type' => 'gmap_map',
            'post_status' => 'publish',
            'posts_per_page' => -1, // Get all maps
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true, // Optimization
            'update_post_meta_cache' => false, // Optimization
            'update_post_term_cache' => false, // Optimization
        ) );

        $map_options = [ '0' => __( '-- Select a Map --', 'google-map-multi-marker' ) ];
        if ( $maps_query->have_posts() ) {
            while ( $maps_query->have_posts() ) {
                $maps_query->the_post();
                $map_options[ get_the_ID() ] = get_the_title();
            }
            wp_reset_postdata();
        } else {
             $map_options['no_maps'] = __( 'No published maps found', 'google-map-multi-marker' );
        }


		$this->add_control(
			'selected_map_id',
			[
				'label' => __( 'Select Map', 'google-map-multi-marker' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => $map_options,
				'default' => '0',
                'description' => __( 'Choose the map you created under Google Maps > All Maps.', 'google-map-multi-marker' ),
			]
		);

		$this->end_controls_section();

        // Optional Override Section
        $this->start_controls_section(
			'override_section',
			[
				'label' => __( 'Display Overrides (Optional)', 'google-map-multi-marker' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

        $this->add_control(
			'override_width',
			[
				'label' => __( 'Width', 'google-map-multi-marker' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'e.g., 100% or 600px', 'google-map-multi-marker' ),
                'description' => __( 'Leave blank to use the width defined in the map settings.', 'google-map-multi-marker' ),
                'label_block' => true,
			]
		);

        $this->add_control(
			'override_height',
			[
				'label' => __( 'Height', 'google-map-multi-marker' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'e.g., 450px', 'google-map-multi-marker' ),
                'description' => __( 'Leave blank to use the height defined in the map settings.', 'google-map-multi-marker' ),
                 'label_block' => true,
			]
		);

         $this->add_control(
			'override_zoom',
			[
				'label' => __( 'Zoom Level', 'google-map-multi-marker' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 22,
				'step' => 1,
                'description' => __( 'Leave blank to use the zoom level defined in the map settings.', 'google-map-multi-marker' ),
			]
		);


        $this->end_controls_section();

	}

	/**
	 * Render widget output on the frontend.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$map_id = absint( $settings['selected_map_id'] );

		if ( ! $map_id || '0' === $settings['selected_map_id'] || 'no_maps' === $settings['selected_map_id'] ) {
            // Check if in Elementor editor mode
            if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'elementor' ) {
                 echo '<p style="padding: 20px; text-align: center; background-color: #f0f0f0; border: 1px dashed #ccc;">' . esc_html__( 'Please select a map from the widget settings.', 'google-map-multi-marker' ) . '</p>';
            }
			return;
		}

        // --- Apply Overrides ---
        $width_override = ! empty( $settings['override_width'] ) ? esc_attr( $settings['override_width'] ) : null;
        $height_override = ! empty( $settings['override_height'] ) ? esc_attr( $settings['override_height'] ) : null;
        // Zoom override would require modifying the shortcode handler or JS, more complex.

        $style_attr = '';
        if ( $width_override || $height_override ) {
            $style_attr = 'style="';
            if ( $width_override ) $style_attr .= 'width: ' . $width_override . ';';
            if ( $height_override ) $style_attr .= 'height: ' . $height_override . ';';
            $style_attr .= '"';
        }

        // --- Render Logic ---
        // In the editor, render the container directly with data attribute
        // On the frontend, continue using the shortcode for consistency elsewhere
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            // Fetch data needed for JS (similar to shortcode logic)
            $map_options = get_post_meta( $map_id, '_gmap_options', true );
            $markers     = get_post_meta( $map_id, '_gmap_markers', true );
            $api_key     = get_option( 'gmap_api_key' ); // Check if API key exists

            if ( empty( $api_key ) ) {
                 echo '<p style="padding: 10px; color: red; text-align: center;">' . esc_html__( 'API Key Missing.', 'google-map-multi-marker' ) . '</p>';
                 return;
            }

            $map_options = is_array($map_options) ? $map_options : [];
            $markers     = is_array($markers) ? $markers : [];

            // Add global default image URLs to map options for Elementor editor preview
            $map_options['default_marker_image'] = get_option( 'gmap_mm_default_marker_icon', GMAP_MM_DEFAULT_MARKER_ICON );
            $map_options['default_tooltip_image'] = get_option( 'gmap_mm_default_tooltip_image', GMAP_MM_DEFAULT_TOOLTIP_IMAGE );

            // Apply overrides if present
            if ($width_override) $map_options['width'] = $width_override;
            if ($height_override) $map_options['height'] = $height_override;
            // Note: Zoom override from widget settings isn't handled here yet, would need JS adjustment

            $map_instance_suffix = wp_rand(100, 999); // Generate unique ID part
            $map_container_id = 'gmap-mm-container-' . esc_attr( $map_id ) . '-' . $map_instance_suffix;

            $data_to_embed = [
                'mapId' => $map_id,
                'containerId' => $map_container_id, // Pass the generated ID
                'options' => $map_options,
                'markers' => $markers,
            ];

            // Prepare map container HTML
            $width = isset($map_options['width']) ? esc_attr($map_options['width']) : '100%';
            $height = isset($map_options['height']) ? esc_attr($map_options['height']) : '400px';
            // Use the $style_attr calculated earlier for the wrapper if needed, or apply directly
            $container_style = 'width: ' . $width . '; height: ' . $height . ';';

            echo '<div class="elementor-gmap-mm-widget-wrapper">'; // Keep outer wrapper consistent
            echo '<div id="' . esc_attr($map_container_id) . '" class="gmap-mm-container" style="' . esc_attr($container_style) . '" data-mapdata="' . esc_attr( wp_json_encode( $data_to_embed ) ) . '">';
            echo '<p>' . esc_html__( 'Loading map...', 'google-map-multi-marker' ) . '</p>'; // Placeholder text
            echo '</div>';
            echo '</div>';

            // Script enqueuing is now handled by the 'elementor/editor/after_enqueue_scripts' hook
            // and the get_script_depends() method. No need for explicit enqueuing here.

        } else {
            // Frontend: Use the shortcode
            echo '<div class="elementor-gmap-mm-widget-wrapper" ' . $style_attr . '>'; // Wrapper for potential style overrides
            echo do_shortcode( '[map-multi-marker id="' . $map_id . '"]' );
            echo '</div>';
        }
	}

	/**
	 * Render widget output in the editor. (Optional - often render() is sufficient)
	 */
	// protected function _content_template() {} // Keep commented out unless needed

    /**
	 * Render plain content for accessibility, search engines etc. (Optional)
	 */
	public function render_plain_content() {
        // Intentionally empty for this widget
    }

} // End of Google_Map_Multi_Marker_Elementor_Widget class

/**
 * Register the Elementor Widget.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
 */
function gmap_mm_register_elementor_widget( $widgets_manager ) {
	$widgets_manager->register( new Google_Map_Multi_Marker_Elementor_Widget() );
}
add_action( 'elementor/widgets/register', 'gmap_mm_register_elementor_widget' );

// Closing PHP tag omitted intentionally
