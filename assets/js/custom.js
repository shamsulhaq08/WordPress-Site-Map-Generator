jQuery(document).ready(function($) {
    $('#sitemap-generator-form').submit(function(event) {
        // Show the progress bar
        $('#progress-bar').css('width', '0%').text('0%');
        $('#progress-bar-container').show();

        // Perform the form submission
        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: $(this).serialize(),
            success: function(response) {
                // Hide the progress bar on success
                $('#progress-bar-container').hide();
                $('#response-container').html(response);
            },
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $('#progress-bar').css('width', percentComplete + '%').text(percentComplete.toFixed(2) + '%');
                    }
                }, false);
                return xhr;
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Handle error
                $('#progress-bar-container').hide();
                $('#response-container').html('<div class="error"><p>' + errorThrown + '</p></div>');
            }
        });

        event.preventDefault(); // Prevent the form from submitting normally
    });
});
