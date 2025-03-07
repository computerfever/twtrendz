(function($) {
    "use strict"; // Start of use strict

// Intergration settings
    function actionAjaxLoading(response) {
        $(`#spinner-loading`).addClass('d-none');
        var alertLabel = 'alert-danger';
        if (response.status) {
            alertLabel = 'alert-success';
        }
        $('#alert-intergration')[0].className = `alert ${alertLabel}`;
        $(`#alert-intergration`).text(response.message);
    }
    function cardIntergration(type)
    {
        var temp = '';
        $(".intergration_row").find(".card.active").each(function(e) {
            $(this).removeClass("active");
        });
        $(`#card_${type}`).addClass("active");

        $("#input_intergration_type").val(type);

        $('.form-intergration').addClass('d-none');
        $(`#form_${type}`).removeClass('d-none');
        $('#alert-intergration')[0].className = 'd-none';

    }
    function initIntergration(item_intergration) {
        cardIntergration(item_intergration.type);
        if (item_intergration.type == "mailchimp") {

            var input = $('#mailchimp_api_key');
            var api_key = input.val();
            
            if (api_key) {
                loadingListMailchimp(input, api_key);
            }
        }
        else if(item_intergration.type == "acellemail"){
            var input = $('#acellemail_api_token');
            var api_endpoint = $('#acellemail_api_endpoint').val();
            var api_token = $('#acellemail_api_token').val();
            if(api_token && api_endpoint){
                loadingListAcellemail(input, api_endpoint,api_token);
            }
        }
    }
    
    $('#intergrations-tab').on('click', function(e) {
        initIntergration(item_intergration);
    });

    $(".intergration_row > .col-md-4 > .card").on('click', function(e) {
        var type = $(this).attr('data-type');
        cardIntergration(type);
    });

    // Mailchimp
    function loadingMergeFieldsMailchimp(list_id, api_key, async = false) {
        $.ajax({
            type: "POST",
            async: async,
            url: url_load_merge_fields + `/mailchimp`,
            data: {
                api_key: api_key,
                list_id: list_id,
                "_token": _token
            },
            beforeSend: function() {
                $(`#spinner-loading`).removeClass('d-none');
            },
            success: function(response) {
                if (response.status == true) {
                    $('#merge_fields_span').text(response.data);
                    $('#mailchimp_merge_fields').val(response.data);
                } else {
                    $('#merge_fields_span').text('');
                    $('#mailchimp_merge_fields').val('');
                }

                $(`#spinner-loading`).addClass('d-none');
            }
        });
    }

    function loadingListMailchimp(input, api_key) {
        $.ajax({
            type: "POST",
            url: url_load_list + `/mailchimp`,
            data: {
                api_key: api_key,
                "_token": _token
            },
            beforeSend: function() {
                $(`#spinner-loading`).removeClass('d-none');
                input.prop('disabled', true);

            },
            success: function(response) {
                if (response.status == false) {
                    input.val('');
                    input.prop('disabled', false);
                    actionAjaxLoading(response);

                } else {

                    input.prop('disabled', false);
                    //select option mailing_list
                    var html_option = ``;
                    response.data.forEach(function(item) {
                        var selected = '';
                        if (item.id == item_intergration.settings['mailing_list']) {
                            selected = ' selected '
                        }
                        html_option += `<option value="${item.id}" ${selected}>${item.name}</option>`;

                    });
                    $('#mailchimp_mailing_list').html(html_option);

                    var list_id = $('#mailchimp_mailing_list :selected').val();

                    if (!list_id) {
                        list_id = $('#mailchimp_mailing_list option:nth-child(1)').val();
                    }

                    if (list_id) loadingMergeFieldsMailchimp(list_id, api_key);

                    actionAjaxLoading(response);
                }
                
                $("#loadingMessage").addClass('d-none');

                return false;

            }
        });
    }
    
    $('#mailchimp_api_key').on('change', function(e) {
        var input = $(this);
        var api_key = $(this).val();
        loadingListMailchimp(input, api_key, 'mailchimp');

    });

    $('#mailchimp_mailing_list').on('change', function(e) {
        var list_id = $(this).val();
        var api_key = $('#mailchimp_api_key').val();
        loadingMergeFieldsMailchimp(list_id, api_key, true);

    });
    // End Mailchimp

    // Acellemail
    function loadingListAcellemail(input, api_endpoint, api_token) {
        $.ajax({
            type: "POST",
            url: url_load_list + `/acellemail`,
            data: {
                api_endpoint: api_endpoint,
                api_token: api_token,
                "_token": _token
            },
            beforeSend: function() {
                $(`#spinner-loading`).removeClass('d-none');
                input.prop('disabled', true);

            },
            success: function(response) {
                if (response.status == false) {
                    input.val('');
                    input.prop('disabled', false);
                    actionAjaxLoading(response);

                } else {

                    input.prop('disabled', false);
                    //select option mailing_list
                    var html_option = ``;
                    response.data.forEach(function(item) {
                        var selected = '';
                        if (item.uid == item_intergration.settings['mailing_list']) {
                            selected = ' selected '
                        }
                        html_option += `<option value="${item.uid}" ${selected}>${item.name}</option>`;

                    });
                    $('#acellemail_mailing_list').html(html_option);

                    var list_id = $('#acellemail_mailing_list :selected').val();

                    if (!list_id) {
                        list_id = $('#acellemail_mailing_list option:nth-child(1)').val();
                    }
                    if (list_id) loadingMergeAcellemail(list_id, api_endpoint, api_token);

                    actionAjaxLoading(response);
                }
                
                $("#loadingMessage").addClass('d-none');

                return false;

            }
        });
    }
    function loadingMergeAcellemail(list_id, api_endpoint, api_token, async = false) {
        $.ajax({
            type: "POST",
            async: async,
            url: url_load_merge_fields + `/acellemail`,
            data: {
                api_endpoint: api_endpoint,
                api_token: api_token,
                list_id: list_id,
                "_token": _token
            },
            beforeSend: function() {
                $(`#spinner-loading`).removeClass('d-none');
            },
            success: function(response) {
                if (response.status == true) {
                    $('#merge_fields_span_acellemail').text(response.data);
                    $('#acellemail_merge_fields').val(response.data);
                } else {
                    $('#merge_fields_span_acellemail').text('');
                    $('#acellemail_merge_fields').val('');
                }
                $(`#spinner-loading`).addClass('d-none');
            }
        });
    }
    $('#acellemail_api_endpoint').on('change', function(e) {
        var input = $(this);
        var api_endpoint = $('#acellemail_api_endpoint').val();
        var api_token = $('#acellemail_api_token').val();
        if(api_token && api_endpoint){
            loadingListAcellemail(input, api_endpoint,api_token);
        }
    });
    $('#acellemail_api_token').on('change', function(e) {
        var input = $(this);
        var api_endpoint = $('#acellemail_api_endpoint').val();
        var api_token = $('#acellemail_api_token').val();
        if(api_token && api_endpoint){
            loadingListAcellemail(input, api_endpoint,api_token);
        }
    });
    $('#acellemail_mailing_list').on('change', function(e) {
        var api_endpoint = $('#acellemail_api_endpoint').val();
        var api_token = $('#acellemail_api_token').val();
        var list_id = $(this).val();
        if(api_token && api_endpoint){
            loadingMergeAcellemail(list_id, api_endpoint, api_token,true);
        }
    });

    // End Acellemail
    
// End Intergration settings

// Autoresponder settings
    tinymce.init({
        selector: '#autoresponder_message_text'
    });
// End autoresponder settings
    

// Font family settings
    $("input[name='search_fonts']").on('change', function() {

        var search_query =  $(this).val();
        $.ajax({
            type: "POST",
            url: url_search_fonts,
            data: {
                search_query: search_query,
                "_token": _token
            },
            beforeSend: function() {
                $('#list_fonts').html('');
                $(`#spinner-loading-fonts`).removeClass('d-none');
            },
            success: function(response) {
                if (response.status == true) {
                    
                    var all_tr_table = '';
                    
                    $.each(response.data, function (i) {
                        var font_variants_option = `<option value="">${lang.select_a_font}</option>`;
                        console.log(response.data[i].variants);
                        $.each(response.data[i].variants, function (j) {
                            font_variants_option += `
                                <option value="${response.data[i].family}:${response.data[i].variants[j]}">
                                ${response.data[i].family}:${response.data[i].variants[j]}
                                </option>`;
                        });
                        all_tr_table+= `
                            <tr>
                                <td>${response.data[i].family}</td>
                                <td>
                                    <div class="d-flex">
                                        <div class="p-1">
                                            <a target="_blank" href="https://fonts.google.com/?query=${response.data[i].family}">
                                            <span class="badge badge-dark">
                                                ${lang.demo_font}
                                            </span>
                                            </a>
                                        </div>
                                        <div class="p-1">
                                            <select class="btn-font-select">
                                                ${font_variants_option}
                                            </select>
                                        </div>
                                    </div>
                                </td>
                            </tr>`;
                    });
                    var data_html_fonts = `
                    <table class="table card-table">
                        <thead class="thead-dark">
                            <tr>
                            <th>${lang.font_name}</th>
                            <th>${lang.action}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${all_tr_table}
                        </tbody>
                    </table>`;

                    $('#list_fonts').html(data_html_fonts);
                }
                $(`#spinner-loading-fonts`).addClass('d-none');
            }
        });
        
    });
    
    $(document).on('change', '.btn-font-select', function(){
        var family = this.value;
        if(family){
            $('#font_currently').val(family);
            $('#font_currently_label').html(family);
            Swal.fire({
                position: 'top-end',
                timer: 3000,
                toast: true,
                html: `<small><i class="fas fa-check-circle text-success"></i> ${lang.selected_font}: '<strong>${family}</strong>'</small>`,
                showConfirmButton: false,
            });
        }
    });
// End Font family settings

})(jQuery); // End of use strict