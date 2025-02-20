<div class="row">
    <div class="col-md-12">
        <div class="sub_section">
            <h2 class="text-semibold text-primary">{{ trans('messages.customer.general') }}</h2>
            <div class="mb-3 {{ $errors->has('name') ? 'has-error' : '' }}">
                <label for="" class="form-label">{{ trans('messages.customer.name') }}</label>
                <input type="text" name="name" value="{{ $customer->name }}" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" />
                @if ($errors->has('name'))
                    <span class="help-block">
                        {{ $errors->first('name') }}
                    </span>
                @endif
            </div>

            @if (config('custom.japan'))
                <input type="hidden" name="timezone" value="Asia/Tokyo" />
            @else
                @include('helpers.form_control', [
                    'type' => 'select',
                    'name' => 'timezone',
                    'value' => $customer->timezone ?? config('app.timezone'),
                    'options' => Tool::getTimezoneSelectOptions(),
                    'include_blank' => trans('messages.choose'),
                    'rules' => ['name' => 'required'],
                ])
            @endif
                

            @if (config('custom.japan'))
                <input type="hidden" name="language_id" value="{{ Acelle\Model\Language::getJapan()->id }}" />
            @else
                @include('helpers.form_control', [
                    'type' => 'select',
                    'name' => 'language_id',
                    'label' => trans('messages.language'),
                    'value' => $customer->language_id ?? \Acelle\Model\Language::getDefaultLanguage()->id,
                    'options' => Acelle\Model\Language::getSelectOptions(),
                    'include_blank' => trans('messages.choose'),
                    'rules' => ['name' => 'required'],
                ])
            @endif

        </div>
    </div>
</div>

<script>
	$(function() {
		// Preview upload image
		$("input.previewable").on('change', function() {
			var img = $("img[preview-for='" + $(this).attr("name") + "']");
			previewImageBrowse(this, img);

            var imput = $(this).parents(".profile-image").find("input[name='_remove_image']");
            imput.val("");
		});
		$(".remove-profile-image").click(function() {
			var img = $(this).parents(".profile-image").find("img");
			var imput = $(this).parents(".profile-image").find("input[name='_remove_image']");
			img.attr("src", img.attr("empty-src"));
			imput.val("true");
		});
	});
</script>