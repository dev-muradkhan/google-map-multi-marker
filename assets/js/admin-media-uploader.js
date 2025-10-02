jQuery(document).ready(function($){
    // Media uploader for default marker icon and tooltip image
    $('.gmap-mm-image-upload-field').on('click', '.gmap-mm-upload-button', function(e) {
        e.preventDefault();

        var button = $(this);
        var parent = button.closest('.gmap-mm-image-upload-field');
        var imagePreview = parent.find('.gmap-mm-image-preview');
        var imageURLInput = parent.find('.gmap-mm-image-url');

        var customUploader = wp.media({
            title: gmap_mm_media_uploader.title,
            button: {
                text: gmap_mm_media_uploader.button
            },
            multiple: false // Set to true to allow multiple files to be selected
        })
        .on('select', function() {
            var attachment = customUploader.state().get('selection').first().toJSON();
            imagePreview.attr('src', attachment.url).show(); // Show image if hidden
            imageURLInput.val(attachment.url);
            parent.find('.gmap-mm-remove-button').show(); // Show remove button
        })
        .open();
    });

    // Handle remove image button click
    $('.gmap-mm-image-upload-field').on('click', '.gmap-mm-remove-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var parent = button.closest('.gmap-mm-image-upload-field');
        var imagePreview = parent.find('.gmap-mm-image-preview');
        var imageURLInput = parent.find('.gmap-mm-image-url');

        imagePreview.attr('src', '').hide(); // Hide image
        imageURLInput.val(''); // Clear the input value
        button.hide(); // Hide remove button
    });
});
