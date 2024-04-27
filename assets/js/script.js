jQuery(document).ready(function($) {
    // Form submission validation
    $('form').submit(function(event) {
        // Get the input URL value
        var websiteUrl = $('#website_url').val();

        // Regular expression to validate URL format
        var urlPattern = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/i;

        // Check if the URL is empty or doesn't match the pattern
        if (websiteUrl === '' || !urlPattern.test(websiteUrl)) {
            // Display error message
            alert('Please enter a valid website URL.');
            // Prevent form submission
            event.preventDefault();
        }
    });
});
