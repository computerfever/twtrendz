@extends('core::layouts.app')
@section('title', __('Leads'))
@section('content')

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">@lang('Leads')</h1>
    @php 
      $export_params = [];
      if(!empty($request_landingpage_code)) {
          $export_params["landingpage_code"] = $request_landingpage_code;
      }
      if(!empty($request_popup_code)) {
          $export_params["popup_code"] = $request_popup_code;
      }
    @endphp
    <a href="
        @if(count($export_params) > 0) 
            {{ route('leads.exportcsv', $export_params) }} 
        @else
            {{ route('leads.exportcsv') }} 
        @endif
    " class="btn btn-primary">@lang('Export all')</a>
</div>
<div class="card">
  <div class="card-body">
      <div class="table-responsive">
          <table class="table" id="data-table">
              <thead>
                  <tr>
                      <th>@lang('Lead Info')</th>
                      <th>@lang('From')</th>
                      <th>@lang('Browser')</th>
                      <th>@lang('OS')</th>
                      <th>@lang('Device')</th>
                      <th>@lang('Dates')</th>
                      <th>@lang('Action')</th>
                  </tr>
              </thead>
          </table>
      </div>
  </div>
</div>
@stop

@push('scripts')
<script>
    const FORMS_TOKEN = '{{ csrf_token() }}';
    const FORMS_DATA_AJAX_URL = "{{ route('leads.ajax') }}";
    const FORMS_DESTROY_URL = "{{ route('leads.destroy') }}";
    @php 
      $tmp_landingpages = '';
      foreach($landingpages as $landingpage) {
        $tmp_landingpages .= '<option value="' . $landingpage->id . '">' . $landingpage->name . '</option>';
      }
    @endphp
    
    var HTML_TABLE_FILTER = ``;

    @if(Module::find('Popup'))
      @php 
        $tmp_popups = '';
        foreach($popups as $popup) {
          $tmp_popups .= '<option value="' . $popup->id . '">' . $popup->name . '</option>';
        }
      @endphp
      HTML_TABLE_FILTER += `<label style="margin-left: 8px;">`;
      HTML_TABLE_FILTER += `<select name="popup_id" class="form-control form-control-sm">`;
      HTML_TABLE_FILTER += `<option value="">All popups</option>`;
      HTML_TABLE_FILTER += '{!! $tmp_popups !!}';
      HTML_TABLE_FILTER += `</select>`;
      HTML_TABLE_FILTER += `</label>`;
    @endif

    HTML_TABLE_FILTER += `<label style="margin-left: 8px;">`;
    HTML_TABLE_FILTER += `<select name="landingpage_id" style="max-width:200px;" class="form-control form-control-sm">`;
    HTML_TABLE_FILTER += `<option value="">All landing pages</option>`;
    HTML_TABLE_FILTER +=  '{!! $tmp_landingpages !!}';
    HTML_TABLE_FILTER += `</select>`;
    HTML_TABLE_FILTER += `</label>`;

    const searchPlaceholder = "@lang('Search lead info')";
    const FORMS_CONFIRM_DELETE_MESSAGE = "@lang('Confirm Delete?')";

</script>
<script src="{{ Module::asset('forms:js/data.js') }}"></script>
@endpush