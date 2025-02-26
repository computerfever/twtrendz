(function($) {
    "use strict"; // Start of use strict
    // load content page
    $.ajax({
        url: window._loadPageLink,
        type: 'POST',
        data: `_token=${window._token}`,
        success: function(data) {
            if ($.isEmptyObject(data.error)) {
                $('head').append(data.thank_custom_header);
                $('body').prepend(data.thank_you_page_html);
                $('body').prepend(`<style>${data.thank_you_page_css}</style>`);
                $('body').prepend(`<style>${data.blockscss}</style>`);
                var font_family = data.fontCurrently.split(':')[0];
                $('head').append(`<link href="https://fonts.googleapis.com/css?family=${data.fontCurrently}&display=swap" rel="stylesheet">`);
                $('body').prepend(`<style>body{font-family: '${font_family}', sans-serif; !important;}</style>`);
                $('body').append(data.thank_custom_footer);
                
                $('#loadingMessage').css('display', 'none');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: data.error,
                });
            }
        },
        error: function(xhr, ajaxOptions, thrownError) {
            console.log(xhr);
        }
    });

})(jQuery); // End of use strict