(function ($) {
  const table = $("#data-table").DataTable({
    language: {
        searchPlaceholder: searchPlaceholder
    },
    processing: true,
    serverSide: true,
    ajax: {
      url: FORMS_DATA_AJAX_URL,
      data: function (d) {
        d.popup_id = $('#data-table_filter select[name="popup_id"]').val();
        d.landingpage_id = $('#data-table_filter select[name="landingpage_id"]').val();
      },
    },
    columns: [
      { data: "lead_info" },
      { data: "from" },
      { data: "browser" },
      { data: "os" },
      { data: "device" },
      { data: "dates" },
      { data: "action" },
    ],
    initComplete: function () {
      this.api().columns().every(function () {
          var column = this;
          var input = document.createElement("input");
          $(input).appendTo($(column.footer()).empty())
          .on('change', function () {
              column.search($(this).val(), false, false, true).draw();
          });
      });
    },
    columnDefs: [{ targets: "no-sort", orderable: false, searchable: false }],
  });

  $("#data-table_filter").append(HTML_TABLE_FILTER);

  $('#data-table_filter select[name="popup_id"]').on("change", function () {
    table.draw();
  });
  $('#data-table_filter select[name="landingpage_id"]').on("change", function () {
    table.draw();
  });

  $(document).on("click", ".btn-delete", function () {
    if (!confirm(FORMS_CONFIRM_DELETE_MESSAGE)) {
      return;
    }
    $(this).attr("disabled", true);
    const id = $(this).data("id");
    const _this = this;
    $.ajax({
      url: FORMS_DESTROY_URL,
      type: "POST",
      data: {
        _token: FORMS_TOKEN,
        id,
      },
      success: function (data) {
        table.draw();
      },
      error: function (xhr, ajaxOptions, thrownError) {
        // handle error
      },
      complete: function () {
        $(_this).removeAttr("disabled");
      },
    });
  });
})(jQuery);
