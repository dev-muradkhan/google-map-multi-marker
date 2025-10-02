/**
 * Frontend Google Map Initialization and Marker Handling.
 * Uses data localized from PHP via `gmapMmData`.
 */

/**
 * Initializes a specific map within a given scope (widget container).
 * @param {jQuery} scope The jQuery object representing the widget container.
 */
async function initializeMapForScope(scope) {
    // Removed early API check - google.maps.importLibrary handles waiting.

    // Find the map container within the provided scope
    const mapContainer = scope.find('.gmap-mm-container').get(0);
    if (!mapContainer) {
        return; // No map in this specific widget instance
    }
    const containerId = mapContainer.id;
    if (!containerId) {
         return;
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

    // Create a single InfoWindow instance (or one per map if needed, but reuse is better)
    // Consider scoping this if multiple maps need independent info windows.
    const infoWindow = new InfoWindow();

    // --- Map Initialization ---
    const mapOptions = {
        zoom: parseInt(mapData.options.zoom) || 8,
        center: {
            lat: parseFloat(mapData.options.lat) || 39.8283,
            lng: parseFloat(mapData.options.lng) || -98.5795
        },
        mapTypeId: mapData.options.map_type || 'roadmap',
        // Add more controls/options as needed from mapData.options
    };

    // Apply custom map styles if available and valid
    if (mapData.options.custom_styles) {
        try {
            const customStyles = JSON.parse(mapData.options.custom_styles);
            if (Array.isArray(customStyles)) {
                mapOptions.styles = customStyles;
                // Remove mapId if custom styles are applied, as they conflict with cloud-based styling
                delete mapOptions.mapId;
            }
        } catch (e) {
            console.error(`[GMM Frontend] Error parsing custom map styles for #${containerId}:`, e);
        }
    } else {
        // Only set mapId if no custom styles are applied, to allow cloud-based styling if desired
        mapOptions.mapId = `GMAP_MM_${mapData.mapId}_${containerId.split('-').pop()}`;
    }

    const map = new Map(mapContainer, mapOptions);

    // Mark container as initialized
    mapContainer.classList.add('gmap-mm-initialized');
    // Remove "Loading map..." text
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
            // If markerData.marker_image is explicitly set (even to an empty string), use it.
            // Otherwise, fall back to the default from map options.
            const markerImageUrl = markerData.marker_image !== undefined && markerData.marker_image !== null
                                   ? markerData.marker_image
                                   : mapData.options.default_marker_image;

            if (MarkerClass === AdvancedMarkerElement) {
                // For AdvancedMarkerElement, use content property
                let markerIconElement = null;
                if (markerImageUrl) { // Only create element if URL is not empty
                    markerIconElement = document.createElement('img');
                    markerIconElement.src = markerImageUrl;
                    markerIconElement.style.width = '32px';
                    markerIconElement.style.height = 'auto';
                    markerIconElement.style.maxWidth = '32px';
                    markerOptions.content = markerIconElement;
                }
            } else {
                // For classic google.maps.Marker, use icon property
                if (markerImageUrl) { // Only set icon if URL is not empty
                    markerOptions.icon = {
                        url: markerImageUrl,
                        scaledSize: new google.maps.Size(32, 40) // Adjust size as needed
                    };
                }
            }

            const marker = new MarkerClass(markerOptions);

            // --- InfoWindow Content ---
            let infoContent = '<div class="gmap-mm-infowindow">';
            // If markerData.tooltip_image is explicitly set (even to an empty string), use it.
            // Otherwise, fall back to the default from map options.
            const tooltipImageUrl = markerData.tooltip_image !== undefined && markerData.tooltip_image !== null
                                    ? markerData.tooltip_image
                                    : mapData.options.default_tooltip_image;

            if (mapData.options.tooltip_show_image === '1' && tooltipImageUrl) { // Only show image if URL is not empty
                 infoContent += `<img src="${escapeHtml(tooltipImageUrl)}" alt="${escapeHtml(markerData.title)}" style="max-width: 150px; height: auto; margin-bottom: 5px;"><br>`;
            }
            if (mapData.options.tooltip_show_title === '1' && markerData.title) {
                infoContent += `<strong>${escapeHtml(markerData.title)}</strong><br>`;
            }
            if (mapData.options.tooltip_show_address === '1' && markerData.address) {
                infoContent += `${escapeHtml(markerData.address)}<br>`;
            }
             if (mapData.options.tooltip_show_phone === '1' && markerData.phone) {
                // Make phone number clickable
                infoContent += `<a href="tel:${escapeHtml(markerData.phone.replace(/[^0-9+]/g, ''))}">${escapeHtml(markerData.phone)}</a><br>`;
            }
            if (mapData.options.tooltip_show_weblink === '1' && markerData.web_link) {
                // Ensure link opens in new tab and add rel="noopener" for security
                infoContent += `<a href="${escapeHtml(markerData.web_link)}" target="_blank" rel="noopener">${escapeHtml(markerData.web_link)}</a><br>`;
            }
            infoContent += '</div>';

            // --- Add Click Listener for InfoWindow ---
            if (infoContent.length > '<div class="gmap-mm-infowindow"></div>'.length) { // Only add listener if there's content
                marker.addListener('click', () => {
                    infoWindow.close(); // Close existing window first
                    infoWindow.setContent(infoContent);
                    infoWindow.open(map, marker);
                });
            }
        });
    } else {
    }

    // Optional: Add map controls based on settings
    // map.setOptions({ mapTypeControl: true, zoomControl: true, fullscreenControl: true });

} // End initializeMapForScope
// Basic HTML escaping function - Alternative DOM Method
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

// Function to initialize all maps found on the page (using gmapMmData)
function initializeAllFrontendMaps() {
    jQuery('.gmap-mm-container').each(function() {
        // Pass the parent element as the scope, assuming it's the widget/shortcode wrapper
        initializeMapForScope(jQuery(this).parent());
    });
}

// --- Initialization Logic ---
// This script should only be loaded on the actual frontend now, thanks to PHP checks.

jQuery(document).ready(function($) {

    // Check if Elementor Frontend API exists (for widgets on the live site)
    if (typeof elementorFrontend !== 'undefined' && typeof elementorFrontend.hooks !== 'undefined') {
        // Use Elementor hook for widgets added via Elementor
        elementorFrontend.hooks.addAction('frontend/element_ready/google-map-multi-marker.default', function($scope) {
            initializeMapForScope($scope); // $scope is the widget wrapper
        });

        // The Elementor hook should handle widget initialization.
        // No need for the initializeAllFrontendMaps fallback here if using the hook.

    } else {
        // If Elementor Frontend is not active, just initialize all maps found
        initializeAllFrontendMaps();
    }
});
