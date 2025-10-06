/**
 * Frontend Google Map Initialization and Marker Handling.
 * Uses data localized from PHP via `gmapMmData`.
 */

/**
 * Initializes a specific map within a given scope.
 * The scope can be the map's container div itself (from a shortcode)
 * or a parent wrapper (from an Elementor widget).
 * @param {jQuery} scope The jQuery object for the container or a wrapper.
 */
async function initializeMapForScope(scope) {
    let mapContainer;

    // ** FIXED: Robustly find the map container **
    // This logic now correctly handles both shortcodes and Elementor widgets.
    if (scope.hasClass('gmap-mm-container')) {
        // Handles the shortcode case where the scope IS the container.
        mapContainer = scope.get(0);
    } else {
        // Handles the Elementor case where the scope is a parent wrapper.
        mapContainer = scope.find('.gmap-mm-container').get(0);
    }

    // If we still haven't found a container, stop.
    if (!mapContainer) {
        return;
    }

    const containerId = mapContainer.id;
    if (!containerId) {
         return; // Map container needs an ID to find its data.
    }

    let mapData = null;
    let dataSource = 'gmapMmData global'; // Default data source

    // 1. Try to get map data from the data-mapdata attribute (used by Elementor editor)
    const dataMapData = mapContainer.getAttribute('data-mapdata');
    if (dataMapData) {
        try {
            mapData = JSON.parse(dataMapData);
            dataSource = 'data-mapdata attribute';
        } catch (e) {
            console.error(`[GMM Frontend] Error parsing data-mapdata attribute for container ID #${containerId}:`, e);
        }
    }

    // 2. If not found in data attribute, try the global gmapMmData variable (used by shortcode)
    if (!mapData && typeof gmapMmData !== 'undefined' && gmapMmData.maps && gmapMmData.maps.length > 0) {
        mapData = gmapMmData.maps.find(map => map.containerId === containerId);
        if (mapData) {
            dataSource = 'gmapMmData global';
        }
    }

    // Final check if we have valid map data
    if (!mapData) {
        console.error(`[GMM Frontend] Could not find valid map data for container #${containerId}.`);
        mapContainer.innerHTML = '<p style="color: red;">Error: Map data not found.</p>'; // Display error in container
        return; // Stop initialization for this map
    }


     // Check if map is already initialized for this container to prevent re-initialization
    if (mapContainer.classList.contains('gmap-mm-initialized')) {
        return;
    }



    // Import necessary libraries using async/await
    let Map, InfoWindow;
    let MarkerClass; // Will be either AdvancedMarkerElement or google.maps.Marker
    let AdvancedMarkerElement; // Keep this for conditional import

    try {
        Map = (await google.maps.importLibrary("maps")).Map;
        InfoWindow = (await google.maps.importLibrary("maps")).InfoWindow;

        // Determine which marker class to use based on custom styles presence
        if (mapData.options.custom_styles) {
            // If custom styles are present, we cannot use mapId, so use the classic Marker
            MarkerClass = google.maps.Marker;
        } else {
            // If no custom styles, use AdvancedMarkerElement (requires mapId)
            AdvancedMarkerElement = (await google.maps.importLibrary("marker")).AdvancedMarkerElement;
            MarkerClass = AdvancedMarkerElement;
        }

    } catch (error) {
        console.error(`Google Map Multi Marker: Error loading Google Maps libraries for #${containerId}:`, error);
        return;
    }

    // Create a single InfoWindow instance
    const infoWindow = new InfoWindow();

    // --- Map Initialization ---
    const mapOptions = {
        zoom: parseInt(mapData.options.zoom) || 8,
        center: {
            lat: parseFloat(mapData.options.lat) || 39.8283,
            lng: parseFloat(mapData.options.lng) || -98.5795
        },
        mapTypeId: mapData.options.map_type || 'roadmap',
    };

    // Apply custom map styles if available and valid
    if (mapData.options.custom_styles) {
        try {
            const customStyles = JSON.parse(mapData.options.custom_styles);
            if (Array.isArray(customStyles)) {
                mapOptions.styles = customStyles;
                delete mapOptions.mapId;
            }
        } catch (e) {
            console.error(`[GMM Frontend] Error parsing custom map styles for #${containerId}:`, e);
        }
    } else {
        mapOptions.mapId = `GMAP_MM_${mapData.mapId}_${containerId.split('-').pop()}`;
    }

    const map = new Map(mapContainer, mapOptions);

    // Mark container as initialized
    mapContainer.classList.add('gmap-mm-initialized');
    const loadingText = mapContainer.querySelector('p');
    if (loadingText) {
        loadingText.remove();
    }


    // --- Marker Creation ---
    if (mapData.markers && Array.isArray(mapData.markers)) {
        mapData.markers.forEach((markerData, markerIndex) => {
            const lat = parseFloat(markerData.latitude);
            const lng = parseFloat(markerData.longitude);

            if (isNaN(lat) || isNaN(lng)) {
                return; // Skip invalid markers
            }

            const markerOptions = {
                map: map,
                position: { lat: lat, lng: lng },
                title: markerData.title || '', // Basic tooltip on hover
            };

            // --- Custom Marker Image ---
            const markerImageUrl = markerData.marker_image !== undefined && markerData.marker_image !== null
                                   ? markerData.marker_image
                                   : mapData.options.default_marker_image;

            if (MarkerClass === AdvancedMarkerElement) {
                let markerIconElement = null;
                if (markerImageUrl) {
                    markerIconElement = document.createElement('img');
                    markerIconElement.src = markerImageUrl;
                    markerIconElement.style.width = '32px';
                    markerIconElement.style.height = 'auto';
                    markerIconElement.style.maxWidth = '32px';
                    markerOptions.content = markerIconElement;
                }
            } else {
                if (markerImageUrl) {
                    markerOptions.icon = {
                        url: markerImageUrl,
                        scaledSize: new google.maps.Size(32, 40) // Adjust size as needed
                    };
                }
            }

            const marker = new MarkerClass(markerOptions);

            // --- InfoWindow Content ---
            // =========================================================================
            // == MODIFICATION START: Dynamically set icon path from localized data ==
            // =========================================================================
            // Use the pluginUrl passed from PHP, with a fallback just in case.
            const iconBaseUrl = (typeof gmapMmData !== 'undefined' && gmapMmData.pluginUrl)
                ? gmapMmData.pluginUrl + 'assets/images/'
                : '/wp-content/plugins/google-map-multi-marker/assets/images/';


            let infoContent = '<div class="gmap-mm-infowindow">';

            // Inject styles directly into the info window for icon alignment and text color
            infoContent += `<style>
                .gmap-mm-infowindow .gmap-mm-info-line { display: flex; align-items: center; }
                .gmap-mm-infowindow .gmap-mm-info-icon { margin-right: 8px; flex-shrink: 0; }
                .gmap-mm-infowindow .gmap-mm-info-line span.gmap-mm-info-text,
                .gmap-mm-infowindow .gmap-mm-info-line a.gmap-mm-info-text {
                    color: #54595F !important;
                    word-break: break-all;
                }
            </style>`;

            const tooltipImageUrl = markerData.tooltip_image !== undefined && markerData.tooltip_image !== null
                                    ? markerData.tooltip_image
                                    : mapData.options.default_tooltip_image;

            if (mapData.options.tooltip_show_image === '1' && tooltipImageUrl) {
                 infoContent += `<img src="${escapeHtml(tooltipImageUrl)}" alt="${escapeHtml(markerData.title)}" style="max-width: 150px; height: auto; margin-bottom: 5px;"><br>`;
            }
            if (mapData.options.tooltip_show_title === '1' && markerData.title) {
                infoContent += `<strong style="display: block; margin-bottom: 5px;">${escapeHtml(markerData.title)}</strong>`;
            }

            // Address with icon
            if (mapData.options.tooltip_show_address === '1' && markerData.address) {
                infoContent += `<div class="gmap-mm-info-line">
                                    <img src="${iconBaseUrl}house-chimney-solid-full.svg" class="gmap-mm-info-icon" alt="Address icon">
                                    <span class="gmap-mm-info-text">${escapeHtml(markerData.address)}</span>
                                </div>`;
            }

            // Phone with icon
            if (mapData.options.tooltip_show_phone === '1' && markerData.phone) {
                infoContent += `<div class="gmap-mm-info-line">
                                    <img src="${iconBaseUrl}phone-solid-full.svg" class="gmap-mm-info-icon" alt="Phone icon">
                                    <a class="gmap-mm-info-text" href="tel:${escapeHtml(markerData.phone.replace(/[^0-9+]/g, ''))}">${escapeHtml(markerData.phone)}</a>
                                </div>`;
            }

            // Web Link with icon
            if (mapData.options.tooltip_show_weblink === '1' && markerData.web_link) {
                infoContent += `<div class="gmap-mm-info-line">
                                    <img src="${iconBaseUrl}globe-solid-full.svg" class="gmap-mm-info-icon" alt="Website icon">
                                    <a class="gmap-mm-info-text" href="${escapeHtml(markerData.web_link)}" target="_blank" rel="noopener">${escapeHtml(markerData.web_link)}</a>
                                </div>`;
            }
            infoContent += '</div>';
            // ===============================================================
            // == MODIFICATION END                                          ==
            // ===============================================================


            // --- Add Click Listener for InfoWindow ---
            if (infoContent.length > '<div class="gmap-mm-infowindow"></div>'.length) {
                marker.addListener('click', () => {
                    infoWindow.close();
                    infoWindow.setContent(infoContent);
                    infoWindow.open(map, marker);
                });
            }
        });
    }

} // End initializeMapForScope


// Basic HTML escaping function
function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') {
        if (unsafe === null || typeof unsafe === 'undefined') return '';
        unsafe = String(unsafe);
    }
    const div = document.createElement('div');
    div.textContent = unsafe;
    return div.innerHTML;
}

// --- Initialization Trigger ---

function initializeAllFrontendMaps() {
    jQuery('.gmap-mm-container').each(function() {
        // Pass the container element itself.
        initializeMapForScope(jQuery(this));
    });
}

jQuery(document).ready(function($) {
    // Check if Elementor Frontend API exists
    if (typeof elementorFrontend !== 'undefined' && typeof elementorFrontend.hooks !== 'undefined') {
        // Use Elementor hook for widgets added via Elementor
        elementorFrontend.hooks.addAction('frontend/element_ready/google-map-multi-marker.default', function($scope) {
            initializeMapForScope($scope); // $scope is the widget wrapper
        });
    } else {
        // If Elementor is not active, initialize all maps found from shortcodes
        initializeAllFrontendMaps();
    }
});