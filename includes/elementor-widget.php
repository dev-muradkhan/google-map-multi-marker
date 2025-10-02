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
		return 'eicon-google-maps';
	}

	/**
	 * Get widget categories.
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'general' ];
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
		return [ 'gmap-mm-editor-script' ];
	}

    /**
	 * Get style dependencies.
	 * Tells Elementor which styles are needed for this widget's preview.
	 * @return array List of style handles.
	 */
    public function get_style_depends() {
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

        // Get available maps
        $maps_query = new WP_Query( array(
            'post_type' => 'gmap_map',
            'post_status' => ['publish', 'private'],
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ) );

        $map_options = [ '0' => __( '-- Select a Map --', 'google-map-multi-marker' ) ];
        if ( $maps_query->have_posts() ) {
            while ( $maps_query->have_posts() ) {
                $maps_query->the_post();
                $map_options[ get_the_ID() ] = get_the_title();
            }
            wp_reset_postdata();
        } else {
             $map_options['0'] = __( 'No published maps found', 'google-map-multi-marker' );
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

		if ( ! $map_id || '0' === $settings['selected_map_id'] ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                 echo '<div class="elementor-alert elementor-alert-info" role="alert">' . esc_html__( 'Please select a map from the widget settings.', 'google-map-multi-marker' ) . '</div>';
            }
			return;
		}

        // --- Render Logic ---
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            // In the EDITOR, render the container directly with data attribute
            $map_options = get_post_meta( $map_id, '_gmap_options', true );
            $markers     = get_post_meta( $map_id, '_gmap_markers', true );
            $api_key     = get_option( 'gmap_api_key' );

            if ( empty( $api_key ) ) {
                 echo '<div class="elementor-alert elementor-alert-danger" role="alert">' . esc_html__( 'API Key Missing.', 'google-map-multi-marker' ) . '</div>';
                 return;
            }

            $map_options = is_array($map_options) ? $map_options : [];
            $markers     = is_array($markers) ? $markers : [];

            // Apply overrides from Elementor controls
            if ( !empty($settings['override_width']) ) $map_options['width'] = $settings['override_width'];
            if ( !empty($settings['override_height']) ) $map_options['height'] = $settings['override_height'];
            if ( !empty($settings['override_zoom']) ) $map_options['zoom'] = $settings['override_zoom'];

            $map_instance_suffix = $this->get_id(); // Use Elementor's unique widget ID
            $map_container_id = 'gmap-mm-container-' . esc_attr( $map_id ) . '-' . $map_instance_suffix;

            $data_to_embed = [
                'mapId' => $map_id,
                'containerId' => $map_container_id,
                'options' => $map_options,
                'markers' => $markers,
            ];

            $width = $map_options['width'] ?? '100%';
            $height = $map_options['height'] ?? '400px';
            $container_style = 'width: ' . esc_attr($width) . '; height: ' . esc_attr($height) . ';';

            echo '<div id="' . esc_attr($map_container_id) . '" class="gmap-mm-container" style="' . esc_attr($container_style) . '" data-mapdata="' . esc_attr( wp_json_encode( $data_to_embed ) ) . '">';
            echo '<p>' . esc_html__( 'Loading map preview...', 'google-map-multi-marker' ) . '</p>';
            echo '</div>';

        } else {
            // On the FRONTEND, build the shortcode with override attributes
            $shortcode_attrs = '';
            if ( !empty($settings['override_width']) ) {
                $shortcode_attrs .= ' width="' . esc_attr($settings['override_width']) . '"';
            }
            if ( !empty($settings['override_height']) ) {
                $shortcode_attrs .= ' height="' . esc_attr($settings['override_height']) . '"';
            }
            // ** FIXED: Add zoom override to the shortcode attributes **
            if ( !empty($settings['override_zoom']) ) {
                $shortcode_attrs .= ' zoom="' . esc_attr($settings['override_zoom']) . '"';
            }

            echo do_shortcode( '[map-multi-marker id="' . $map_id . '"' . $shortcode_attrs . ']' );
        }
	}
}

/**
 * Register the Elementor Widget.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
 */
function gmap_mm_register_elementor_widget( $widgets_manager ) {
	$widgets_manager->register( new Google_Map_Multi_Marker_Elementor_Widget() );
}
add_action( 'elementor/widgets/register', 'gmap_mm_register_elementor_widget' );

