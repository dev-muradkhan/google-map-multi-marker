(function($) {
    'use strict';

/**
 * Google Map Multi Marker - Elementor Editor Script
 *
 * @param {jQuery} $scope The widget container element.
 */
async function initializeEditorMap($scope) { // Make function async
    var $mapContainer = $scope.find('.gmap-mm-container');
        if ($mapContainer.length === 0) {
             // Let's check if the scope itself IS the container (less likely but possible)
             if ($scope.hasClass('gmap-mm-container')) {
                 $mapContainer = $scope;
             } else {
                 return; // Stop if container not found
             }
        }

        if ($mapContainer.length > 0) {
            var mapDataJson = $mapContainer.data('mapdata');

            if (mapDataJson) {
                try {
                    var mapData = typeof mapDataJson === 'string' ? JSON.parse(mapDataJson) : mapDataJson;

                    // --- Use Modern API Loading ---
                    if (!mapData || !mapData.options || !mapData.markers) {
                         $mapContainer.html('<p style="color: red;">Error: Map data incomplete.</p>');
                         return;
                    }

                    var mapOptionsData = mapData.options;
                    var markersData = mapData.markers;
                    var containerId = mapData.containerId || $mapContainer.attr('id'); // Use ID from data or element

                    if (!containerId) {
                        $mapContainer.html('<p style="color: red;">Error: Map container ID missing.</p>');
                        return;
                    }

                    var mapElement = document.getElementById(containerId);
                    if (!mapElement) {
                         // Attempt to use the found jQuery element directly if ID fails
                         if ($mapContainer.length > 0) {
                            mapElement = $mapContainer[0];
                         } else {
                            return;
                         }
                    }

                    // Import necessary libraries using async/await
                    let Map, AdvancedMarkerElement, InfoWindow;
                    try {
                        Map = (await google.maps.importLibrary("maps")).Map;
                        AdvancedMarkerElement = (await google.maps.importLibrary("marker")).AdvancedMarkerElement;
                        InfoWindow = (await google.maps.importLibrary("maps")).InfoWindow; // Already imported maps, but ok
                    } catch (error) {
                        console.error(`[GMM Editor] Error loading Google Maps libraries for #${containerId}:`, error);
                        $mapContainer.html('<p style="color: red;">Error: Could not load Google Maps libraries.</p>');
                        return;
                    }

                    $(mapElement).empty();

                    // Create a single InfoWindow instance
                    const infoWindow = new InfoWindow();

                    // --- Map Initialization ---
                    const mapConfig = {
                        center: { lat: parseFloat(mapOptionsData.lat) || 0, lng: parseFloat(mapOptionsData.lng) || 0 },
                        zoom: parseInt(mapOptionsData.zoom) || 8,
                        mapId: `GMAP_MM_EDITOR_${mapData.mapId || 'default'}`, // Unique mapId for editor instance
                        mapTypeId: mapOptionsData.map_type || 'roadmap'
                        // Add other map options from mapOptionsData if needed
                    };
                    const map = new Map(mapElement, mapConfig);

                    // --- Marker Creation ---
                    if (Array.isArray(markersData)) {
                        markersData.forEach(function(markerInfo, index) {
                            const lat = parseFloat(markerInfo.latitude);
                            const lng = parseFloat(markerInfo.longitude);

                            if (isNaN(lat) || isNaN(lng)) {
                                return; // Skip invalid markers
                            }

                            const markerOptions = {
                                map: map,
                                position: { lat: lat, lng: lng },
                                title: markerInfo.title || ''
                            };

                            // --- Custom Marker Image (using AdvancedMarkerElement) ---
                            let markerIconElement = null;
                            const markerImageUrl = markerInfo.marker_image !== undefined && markerInfo.marker_image !== null
                                                   ? markerInfo.marker_image
                                                   : mapOptionsData.default_marker_image;
                            if (markerImageUrl) {
                                markerIconElement = document.createElement('img');
                                markerIconElement.src = markerImageUrl;
                                markerIconElement.style.width = '32px';
                                markerIconElement.style.height = 'auto';
                                markerIconElement.style.maxWidth = '32px';
                                markerOptions.content = markerIconElement;
                            }

                            const marker = new AdvancedMarkerElement(markerOptions);

                            // --- InfoWindow Content ---
                            // Use the pluginUrl passed from PHP via gmapMmEditorData, with a fallback.
                            const iconBaseUrl = (typeof gmapMmEditorData !== 'undefined' && gmapMmEditorData.pluginUrl)
                                ? gmapMmEditorData.pluginUrl + 'assets/images/'
                                : '/wp-content/plugins/google-map-multi-marker/assets/images/';

                            let infoContent = '<div class="gmap-mm-infowindow">';

                            // Inject styles directly into the info window
                            infoContent += `<style>
                                .gmap-mm-infowindow .gmap-mm-info-line { display: flex; align-items: center;}
                                .gmap-mm-infowindow .gmap-mm-info-icon { margin-right: 8px; flex-shrink: 0; }
                                .gmap-mm-infowow .gmap-mm-info-line span.gmap-mm-info-text,
                                .gmap-mm-infowindow .gmap-mm-info-line a.gmap-mm-info-text {
                                    color: #54595F !important;
                                    word-break: break-all;
                                }
                            </style>`;

                            const tooltipImageUrl = markerInfo.tooltip_image !== undefined && markerInfo.tooltip_image !== null
                                                    ? markerInfo.tooltip_image
                                                    : mapOptionsData.default_tooltip_image;

                            if (mapOptionsData.tooltip_show_image === '1' && tooltipImageUrl) {
                                 infoContent += `<img src="${escapeHtml(tooltipImageUrl)}" alt="${escapeHtml(markerInfo.title)}" style="max-width: 150px; height: auto; margin-bottom: 5px;"><br>`;
                            }
                            if (mapOptionsData.tooltip_show_title === '1' && markerInfo.title) {
                                infoContent += `<strong style="display: block; margin-bottom: 5px;">${escapeHtml(markerInfo.title)}</strong>`;
                            }
                            if (mapOptionsData.tooltip_show_address === '1' && markerInfo.address) {
                                infoContent += `<div class="gmap-mm-info-line">
                                                    <img src="${iconBaseUrl}house-chimney-solid-full.svg" class="gmap-mm-info-icon" alt="Address icon">
                                                    <span class="gmap-mm-info-text">${escapeHtml(markerInfo.address)}</span>
                                                </div>`;
                            }
                             if (mapOptionsData.tooltip_show_phone === '1' && markerInfo.phone) {
                                infoContent += `<div class="gmap-mm-info-line">
                                                    <img src="${iconBaseUrl}phone-solid-full.svg" class="gmap-mm-info-icon" alt="Phone icon">
                                                    <a class="gmap-mm-info-text" href="tel:${escapeHtml(markerInfo.phone.replace(/[^0-9+]/g, ''))}">${escapeHtml(markerInfo.phone)}</a>
                                                </div>`;
                            }
                            if (mapOptionsData.tooltip_show_weblink === '1' && markerInfo.web_link) {
                                infoContent += `<div class="gmap-mm-info-line">
                                                    <img src="${iconBaseUrl}globe-solid-full.svg" class="gmap-mm-info-icon" alt="Website icon">
                                                    <a class="gmap-mm-info-text" href="${escapeHtml(markerInfo.web_link)}" target="_blank" rel="noopener">${escapeHtml(markerInfo.web_link)}</a>
                                                </div>`;
                            }
                            infoContent += '</div>';

                            // --- Add Click Listener for InfoWindow ---
                            if (infoContent.length > '<div class="gmap-mm-infowindow"></div>'.length) {
                                marker.addListener('gmp-click', () => { // Use 'gmp-click' for AdvancedMarkerElement
                                    infoWindow.close();
                                    infoWindow.setContent(infoContent);
                                    infoWindow.open(map, marker);
                                });
                            }
                        });
                    }

                    // Trigger a resize event slightly after initialization
                    setTimeout(function() {
                        google.maps.event.trigger(map, 'resize');
                        map.setCenter({ lat: parseFloat(mapOptionsData.lat) || 0, lng: parseFloat(mapOptionsData.lng) || 0 });
                    }, 150);

                } catch (e) {
                    console.error('[GMM Editor] Error parsing map data JSON or initializing map:', e);
                     $mapContainer.html('<p style="color: red;">Error: Could not parse map data or initialize map.</p>');
                }
            } else {
                 $mapContainer.html('<p style="color: orange;">Warning: Map data attribute missing.</p>'); // Keep placeholder or show warning
            }
        }
    }

    // Basic HTML escaping function (duplicate from frontend, consider common utility)
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
    $( window ).on( 'elementor/frontend/init', () => {
        if (typeof elementorFrontend !== 'undefined' && typeof elementorFrontend.hooks !== 'undefined') {
            elementorFrontend.hooks.addAction('frontend/element_ready/google-map-multi-marker.default', function($scope) {
                initializeEditorMap($scope); // Initialize the map when the widget is ready
            });
        } else {
            console.error('[GMM Editor] Elementor frontend API not found after init event.');
        }
    });

    // Fallback basic ready check
    jQuery(document).ready(function($) {
    });

})(jQuery);