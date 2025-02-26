	<div class="form-intergration" id="form_acellemail">
		<!-- <h4>@lang('Acellemail')</h4> -->
		<div class="alert alert-warning d-none" role="alert">
			@lang('The form will subscribe a new contact or lead to the chosen mailing system. Make sure there is an <strong>email</strong> field in the form!')
		</div>

		@if($item_intergration->type != "acellemail")
			<input type="text" hidden="" name="acellemail[merge_fields]" id="acellemail_merge_fields" value="" class="form-control">
			<div class="form-group d-none">
				<label class="form-label">@lang('API Endpoint')<span class="text-danger">*</span></label>
				<input type="url" id="acellemail_api_endpoint" name="acellemail[api_endpoint]" value="{{url('api/v1')}}" class="form-control">
			</div>
			<div class="form-group d-none">
				<label class="form-label">@lang('API token')<span class="text-danger">*</span></label>
				<input type="text" id="acellemail_api_token" name="acellemail[api_token]" value="{{Auth::user()->api_token}}" class="form-control">
			</div>
			<div class="form-group">
				<label class="form-label">@lang("Select Mailing list") <span class="text-danger">*</span></label>
				<select id="acellemail_mailing_list" name="acellemail[mailing_list]" class="select">
				</select>
			</div>
			<div class="alert alert-info" role="alert">
				@lang('Valid fields from your list'): <strong id="merge_fields_span_acellemail"></strong>
		   </div>
		   <div class="alert alert-primary" role="alert">
			   @lang('Change name your form fields with fields in your chosen integration, so that the data is saved correctly').<br>
			   @lang('We suggest using String type fields in AcelleMail lists')
		   </div>
			
		@else
			<input type="text" hidden="" name="acellemail[merge_fields]" id="acellemail_merge_fields" value="{{$item_intergration->settings->merge_fields}}" class="form-control">
			<div class="form-group d-none">
				<label class="form-label">@lang('API Endpoint')<span class="text-danger">*</span></label>
				<input type="url" id="acellemail_api_endpoint" name="acellemail[api_endpoint]" value="{{$item_intergration->settings->api_endpoint}}" placeholder="@lang('Your acellemail endpoint https://demo.acellemail.com/api/v1')" class="form-control">
			</div>
			<div class="form-group d-none">
				<label class="form-label">@lang('API token')<span class="text-danger">*</span></label>
				<input type="text" id="acellemail_api_token" name="acellemail[api_token]" value="{{$item_intergration->settings->api_token}}" placeholder="@lang('Your acellemail API token OMVRVE986THjQZ...')" class="form-control">
			</div>
			<div class="form-group">
				<label class="form-label">@lang("Mailing list")<span class="text-danger">*</span></label>
				<select id="acellemail_mailing_list" name="acellemail[mailing_list]" class="select">
					<option value="{{$item_intergration->settings->mailing_list}}" selected>Select Mailing list</option>
				</select>
			</div>
			<div class="alert alert-info" role="alert">
				@lang('Valid fields from your list'): <strong id="merge_fields_span_acellemail"></strong>
		   </div>
		   <div class="alert alert-primary" role="alert">
			   @lang('Change name your form fields with fields in your chosen integration, so that the data is saved correctly').<br>
			   @lang('We suggest using String type fields in AcelleMail lists')
		   </div>

		   
		@endif
	</div>

