{{ csrf_field() }}

<div class="row">
	<div class="col-sm-6 col-md-6">
		<label class="mb-3 font-weight-semibold">{{ trans('messages.domain_name') }}</label>
		<div class="tracking-domain-scheme-name">			
			@include('helpers.form_control', [
				'type' => 'select',
				'class' => '',
				'name' => 'scheme',
				'label' => '',
				'value' => $domain->scheme,
				'help_class' => 'tracking_domain',
				'options' => [
					['text' => 'HTTPS', 'value' => 'https'],
				],
			])
			@include('helpers.form_control', [
				'type' => 'text',
				'class' => '',
				'name' => 'name',
				'label' => '',
				'value' => $domain->name,
				'help_class' => 'tracking_domain',
			])
		</div>
	</div>
</div>
<div class="row">
	<div class="col-sm-6 col-md-6">
		<div class="form-group checkbox-right-switch mt-4">
			<div class="d-flex">	
				<div class="d-flex align-items-top me-3">
					<label>
						<input type="radio" name="verification_method"
							{{ !$domain->verification_method || $domain->verification_method == Acelle\Model\TrackingDomain::VERIFICATION_METHOD_AUTOSSL ? 'checked' : '' }}
							value="{{ Acelle\Model\TrackingDomain::VERIFICATION_METHOD_AUTOSSL }}" class="styled">
					</label>
				</div>
				<div style="width:100%">
					<label>
						{{ trans('messages.tracking_domain.method_autossl') }} 
						<span class="checkbox-description">
							{{ trans('messages.tracking_domain.method_autossl.intro') }} 
						</span>
					   
				   </label>
				</div>
	   		</div>
		</div>
	</div>
</div>
<hr >
<div class="text-left">
	<button class="btn btn-secondary me-2"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
</div>
