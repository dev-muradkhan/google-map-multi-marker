jQuery(document).ready(function($) {
    'use strict';

    // --- Marker Management Table Logic ---
    var markerTableBody = $('#gmap-markers-list');
    var markerTemplateHtml = $('#gmap-marker-template-row').prop('outerHTML'); // Get template HTML
    var postId = $('#gmap-markers-container').data('postId');

    // Function to generate a unique ID (simple version)
    function generateUUID() {
        return 'marker-' + Date.now() + '-' + Math.random().toString(36).substring(2, 9);
    }

    // Function to add a marker row to the table
    function addMarkerRow(markerData) {
        var newRowHtml = markerTemplateHtml;
        var markerId = markerData.id || generateUUID(); // Use existing ID or generate new one

        // Create a temporary element to parse and manipulate the template
        var $tempRow = $(newRowHtml);
        $tempRow.attr('id', 'marker-row-' + markerId); // Set unique ID for the row element
        $tempRow.attr('data-marker-id', markerId);
        $tempRow.removeClass('editable'); // Start as readonly by default if data is provided

        // Populate inputs with data
        $tempRow.find('input[name="title"]').val(markerData.title || '');
        $tempRow.find('input[name="address"]').val(markerData.address || '');
        $tempRow.find('input[name="latitude"]').val(markerData.latitude || '');
        $tempRow.find('input[name="longitude"]').val(markerData.longitude || '');
        $tempRow.find('input[name="phone"]').val(markerData.phone || '');
        $tempRow.find('input[name="web_link"]').val(markerData.web_link || '');

        // Handle marker_image
        var markerImage = markerData.marker_image || ''; // Use empty string if no image, not default
        $tempRow.find('input[name="marker_image"]').val(markerImage);
        var $markerImagePreview = $tempRow.find('.gmap-mm-image-upload-field img.gmap-mm-image-preview[src*="icon-marker.png"]');
        $markerImagePreview.attr('src', markerImage);
        if (markerImage) {
            $markerImagePreview.show();
            $tempRow.find('.gmap-mm-image-upload-field .gmap-mm-remove-button').show();
        } else {
            $markerImagePreview.hide();
            $tempRow.find('.gmap-mm-image-upload-field .gmap-mm-remove-button').hide();
        }

        // Handle tooltip_image
        var tooltipImage = markerData.tooltip_image || ''; // Use empty string if no image, not default
        $tempRow.find('input[name="tooltip_image"]').val(tooltipImage);
        var $tooltipImagePreview = $tempRow.find('.gmap-mm-image-upload-field img.gmap-mm-image-preview[src*="desc-marker.jpg"]');
        $tooltipImagePreview.attr('src', tooltipImage);
        if (tooltipImage) {
            $tooltipImagePreview.show();
            $tempRow.find('.gmap-mm-image-upload-field .gmap-mm-remove-button').show();
        } else {
            $tooltipImagePreview.hide();
            $tempRow.find('.gmap-mm-image-upload-field .gmap-mm-remove-button').hide();
        }

        // Set initial state (readonly)
        setRowState($tempRow, false); // false = not editable

        markerTableBody.append($tempRow);
    }

    // Function to set row state (editable or readonly)
    function setRowState($row, isEditable) {
        if (isEditable) {
            $row.addClass('editable');
            $row.find('input').prop('readonly', false);
            $row.find('.gmap-mm-save-marker, .gmap-mm-cancel-edit').show();
            $row.find('.gmap-mm-edit-marker, .gmap-mm-delete-marker').hide();

            // Show/hide upload/remove buttons based on current image presence
            $row.find('.gmap-mm-image-upload-field').each(function() {
                var $field = $(this);
                var imageUrl = $field.find('.gmap-mm-image-url').val();
                if (imageUrl) {
                    $field.find('.gmap-mm-upload-button').show();
                    $field.find('.gmap-mm-remove-button').show();
                } else {
                    $field.find('.gmap-mm-upload-button').show(); // Always show upload button when editable
                    $field.find('.gmap-mm-remove-button').hide();
                }
            });

        } else {
            $row.removeClass('editable');
            $row.find('input').prop('readonly', true);
            $row.find('.gmap-mm-save-marker, .gmap-mm-cancel-edit').hide();
            $row.find('.gmap-mm-edit-marker, .gmap-mm-delete-marker').show();

            // Hide upload/remove buttons when not editable
            $row.find('.gmap-mm-image-upload-field .gmap-mm-upload-button, .gmap-mm-image-upload-field .gmap-mm-remove-button').hide();
        }
         // Always hide spinner initially
        $row.find('.spinner').removeClass('is-active');
    }

    // Function to get marker data from a row
    function getMarkerDataFromRow($row) {
        var data = {
            id: $row.data('markerId'), // Get the unique marker ID
            title: $row.find('input[name="title"]').val().trim(),
            address: $row.find('input[name="address"]').val().trim(),
            latitude: $row.find('input[name="latitude"]').val().trim(),
            longitude: $row.find('input[name="longitude"]').val().trim(),
            phone: $row.find('input[name="phone"]').val().trim(),
            web_link: $row.find('input[name="web_link"]').val().trim(),
            marker_image: $row.find('input[name="marker_image"]').val().trim(),
            tooltip_image: $row.find('input[name="tooltip_image"]').val().trim() // Add tooltip image
        };
        // Basic validation
        if (!data.latitude || !data.longitude) {
            alert('Latitude and Longitude are required.');
            return null;
        }
        if (isNaN(parseFloat(data.latitude)) || isNaN(parseFloat(data.longitude))) {
             alert('Latitude and Longitude must be valid numbers.');
            return null;
        }
        return data;
    }

    // Load initial markers
    if (typeof gmapMmInitialMarkers !== 'undefined' && Array.isArray(gmapMmInitialMarkers)) {
        gmapMmInitialMarkers.forEach(function(marker) {
            addMarkerRow(marker);
        });
    }

    // --- Event Handlers ---

    // Add Marker Button
    $('#gmap-mm-add-marker-button').on('click', function() {
        var $newRow = $(markerTemplateHtml);
        var newId = generateUUID();
        $newRow.attr('id', 'marker-row-' + newId);
        $newRow.attr('data-marker-id', newId);
        $newRow.addClass('editable new-row'); // Mark as new and editable
        setRowState($newRow, true);
        $newRow.find('.gmap-mm-edit-marker, .gmap-mm-delete-marker').hide(); // Hide edit/delete for new rows

        // Set default images for new row (if any) and manage visibility
        var defaultMarkerIcon = gmapMmAjax.defaultMarkerIcon;
        var $newMarkerImageInput = $newRow.find('input[name="marker_image"]');
        var $newMarkerImagePreview = $newRow.find('.gmap-mm-image-upload-field img.gmap-mm-image-preview[src*="icon-marker.png"]');
        var $newMarkerRemoveButton = $newRow.find('.gmap-mm-image-upload-field .gmap-mm-remove-button');

        $newMarkerImageInput.val(defaultMarkerIcon);
        $newMarkerImagePreview.attr('src', defaultMarkerIcon);
        if (defaultMarkerIcon) {
            $newMarkerImagePreview.show();
            $newMarkerRemoveButton.show();
        } else {
            $newMarkerImagePreview.hide();
            $newMarkerRemoveButton.hide();
        }

        var defaultTooltipImage = gmapMmAjax.defaultTooltipImage;
        var $newTooltipImageInput = $newRow.find('input[name="tooltip_image"]');
        var $newTooltipImagePreview = $newRow.find('.gmap-mm-image-upload-field img.gmap-mm-image-preview[src*="desc-marker.jpg"]');
        var $newTooltipRemoveButton = $newRow.find('.gmap-mm-image-upload-field .gmap-mm-remove-button');

        $newTooltipImageInput.val(defaultTooltipImage);
        $newTooltipImagePreview.attr('src', defaultTooltipImage);
        if (defaultTooltipImage) {
            $newTooltipImagePreview.show();
            $newTooltipRemoveButton.show();
        } else {
            $newTooltipImagePreview.hide();
            $newTooltipRemoveButton.hide();
        }

        markerTableBody.append($newRow);
        $newRow.find('input[name="title"]').focus(); // Focus first field
    });

    // Save Marker Button (delegated)
    markerTableBody.on('click', '.gmap-mm-save-marker', function() {
        var $button = $(this);
        var $row = $button.closest('tr');
        var markerData = getMarkerDataFromRow($row);
        var isNew = $row.hasClass('new-row');
        var ajaxAction = isNew ? 'gmap_mm_add_marker' : 'gmap_mm_edit_marker';

        if (!markerData) return; // Validation failed

        $row.find('.spinner').addClass('is-active');
        $button.prop('disabled', true);

        $.ajax({
            url: gmapMmAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: ajaxAction,
                nonce: gmapMmAjax.saveNonce,
                post_id: postId,
                marker_id: markerData.id, // Send marker ID for edits
                marker: markerData
            },
            success: function(response) {
                if (response.success) {
                    // Update row data attribute and state
                    $row.attr('data-marker-id', response.data.marker.id || markerData.id); // Update ID if provided by backend (for new markers)
                    $row.data('markerId', response.data.marker.id || markerData.id);
                    $row.removeClass('new-row');
                    setRowState($row, false); // Make readonly
                    alert(response.data.message || 'Marker saved.');
                } else {
                    alert('Error: ' + (response.data.message || 'Could not save marker.'));
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('AJAX Error: ' + textStatus + ' - ' + errorThrown);
            },
            complete: function() {
                $row.find('.spinner').removeClass('is-active');
                $button.prop('disabled', false);
            }
        });
    });

    // Edit Marker Button (delegated)
    markerTableBody.on('click', '.gmap-mm-edit-marker', function() {
        var $row = $(this).closest('tr');
        // Store original values in case of cancel
        $row.find('input').each(function() {
            $(this).data('originalValue', $(this).val());
        });
        setRowState($row, true); // Make editable
    });

    // Cancel Edit Button (delegated)
    markerTableBody.on('click', '.gmap-mm-cancel-edit', function() {
        var $row = $(this).closest('tr');
        if ($row.hasClass('new-row')) {
            $row.remove(); // Remove unsaved new row
        } else {
            // Restore original values
            $row.find('input').each(function() {
                $(this).val($(this).data('originalValue'));
            });
            // Restore image previews and button visibility
            $row.find('.gmap-mm-image-upload-field').each(function() {
                var $field = $(this);
                var $imageInput = $field.find('.gmap-mm-image-url');
                var $imagePreview = $field.find('.gmap-mm-image-preview');
                var $removeButton = $field.find('.gmap-mm-remove-button');
                var originalValue = $imageInput.data('originalValue');

                $imageInput.val(originalValue);
                $imagePreview.attr('src', originalValue);

                if (originalValue) {
                    $imagePreview.show();
                    $removeButton.show();
                } else {
                    $imagePreview.hide();
                    $removeButton.hide();
                }
            });
            setRowState($row, false); // Make readonly again
        }
    });

    // Delete Marker Button (delegated)
    markerTableBody.on('click', '.gmap-mm-delete-marker', function() {
        if (!confirm('Are you sure you want to delete this marker?')) {
            return;
        }

        var $button = $(this);
        var $row = $button.closest('tr');
        var markerId = $row.data('markerId');

        $row.find('.spinner').addClass('is-active');
        $button.prop('disabled', true);
        $row.find('.gmap-mm-edit-marker').prop('disabled', true); // Disable edit too

        $.ajax({
            url: gmapMmAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gmap_mm_delete_marker',
                nonce: gmapMmAjax.saveNonce, // Use the same nonce for simplicity here
                post_id: postId,
                marker_id: markerId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() { $(this).remove(); });
                    alert(response.data.message || 'Marker deleted.');
                } else {
                    alert('Error: ' + (response.data.message || 'Could not delete marker.'));
                    $button.prop('disabled', false);
                    $row.find('.gmap-mm-edit-marker').prop('disabled', false);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('AJAX Error: ' + textStatus + ' - ' + errorThrown);
                $button.prop('disabled', false);
                 $row.find('.gmap-mm-edit-marker').prop('disabled', false);
            },
            complete: function() {
                 // Spinner is removed with the row on success
                 if (!$row.is(':visible')) { // Check if row was removed
                     $row.find('.spinner').removeClass('is-active');
                 }
            }
        });
    });

    // Media Uploader for Marker Images (delegated)
    markerTableBody.on('click', '.gmap-mm-image-upload-field .gmap-mm-upload-button', function(e) {
        e.preventDefault();
        // console.log('Media uploader button clicked!'); // Removed debug log

        var button = $(this);
        var parent = button.closest('.gmap-mm-image-upload-field');
        var imagePreview = parent.find('.gmap-mm-image-preview');
        var imageURLInput = parent.find('.gmap-mm-image-url');
        // console.log('Image preview element:', imagePreview); // Removed debug log
        // console.log('Image URL input element:', imageURLInput); // Removed debug log

        var customUploader = wp.media({
            title: 'Select Image', // Use a generic title
            button: {
                text: 'Use this image' // Use a generic button text
            },
            multiple: false
        })
        .on('select', function() {
            var attachment = customUploader.state().get('selection').first().toJSON();
            imagePreview.attr('src', attachment.url).show(); // Show image if hidden
            imageURLInput.val(attachment.url);
            parent.find('.gmap-mm-remove-button').show(); // Show remove button
        })
        .open();
    });

    // Handle remove image button click (delegated for dynamic rows)
    markerTableBody.on('click', '.gmap-mm-image-upload-field .gmap-mm-remove-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var parent = button.closest('.gmap-mm-image-upload-field');
        var imagePreview = parent.find('.gmap-mm-image-preview');
        var imageURLInput = parent.find('.gmap-mm-image-url');

        imagePreview.attr('src', '').hide(); // Hide image
        imageURLInput.val(''); // Clear the input value
        button.hide(); // Hide remove button
    });


    // --- CSV Import ---
    $('#gmap-mm-import-csv-button').on('click', function() {
        var $button = $(this);
        var $fileInput = $('#gmap-mm-csv-file');
        var $spinner = $button.next('.spinner');
        var $status = $('#gmap-mm-import-status');

        if (!$fileInput[0].files.length) {
            alert('Please select a CSV file to import.');
            return;
        }

        var file = $fileInput[0].files[0];
        if (file.type !== 'text/csv' && file.type !== 'application/vnd.ms-excel') {
             alert('Invalid file type. Please upload a CSV file.');
             return;
        }

        var formData = new FormData();
        formData.append('action', 'gmap_mm_import_markers');
        formData.append('nonce', gmapMmAjax.importNonce);
        formData.append('post_id', postId);
        formData.append('csv_file', file);

        $spinner.addClass('is-active');
        $button.prop('disabled', true);
        $status.text('Importing...').css('color', 'inherit').show();

        $.ajax({
            url: gmapMmAjax.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false, // Prevent jQuery from processing the data
            contentType: false, // Prevent jQuery from setting contentType
            success: function(response) {
                if (response.success) {
                    $status.text(response.data.message || 'Import successful!').css('color', 'green');
                    // Add new rows for imported markers
                    if (response.data.imported_markers && Array.isArray(response.data.imported_markers)) {
                        response.data.imported_markers.forEach(function(marker) {
                            addMarkerRow(marker); // Add each imported marker to the table
                        });
                    }
                    $fileInput.val(''); // Clear file input
                } else {
                    $status.text('Error: ' + (response.data.message || 'Import failed.')).css('color', 'red');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                 $status.text('AJAX Error: ' + textStatus + ' - ' + errorThrown).css('color', 'red');
            },
            complete: function() {
                $spinner.removeClass('is-active');
                $button.prop('disabled', false);
                 // Optionally hide status after a delay
                 setTimeout(function() { $status.fadeOut(); }, 5000);
            }
        });
    });

});
