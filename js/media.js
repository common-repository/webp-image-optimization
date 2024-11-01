// js/media.js

jQuery(document).ready(function($) {
    /**
     * Handle "Convert to WebP" button click
     */
    $(document).on('click', '.convert-to-webp', function(e) {
        e.preventDefault();

        var button = $(this);
        var attachmentId = button.data('attachment-id');

        if (!attachmentId) {
            alert('Invalid attachment ID.');
            return;
        }

        // Disable the button to prevent multiple clicks and update its text
        button.prop('disabled', true);
        var originalButtonText = button.text();
        button.text('Converting...');

        // Prepare the data to be sent in the POST request
        var data = {
            action: 'webp_convert_attachment',
            nonce: webpImageOptimization.nonce,
            attachment_id: attachmentId
        };

        // Send AJAX POST request using jQuery
        $.post(webpImageOptimization.ajax_url, data, function(response) {
            if (response.success) {
                alert('Image successfully converted to WebP.');
                // Optionally, refresh the page or update the image preview
                location.reload(); // Refresh to see changes
            } else {
                alert('Conversion failed: ' + response.data);
                // Re-enable the button in case of failure
                button.prop('disabled', false);
                button.text(originalButtonText);
            }
        }).fail(function(xhr, status, error) {
            alert('An error occurred: ' + error);
            // Re-enable the button in case of error
            button.prop('disabled', false);
            button.text(originalButtonText);
        });
    });
});
