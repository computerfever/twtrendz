<div class="row">
    <div class="col-md-3">
        <div class="sub_section">
            <h2 class="text-semibold text-primary">{{ trans('messages.user.photo') }}</h2>
            <div class="media profile-image">
                <div class="media-left">
                    <a href="#" class="upload-media-container">
                        <img preview-for="image" empty-src="{{ URL::asset('images/placeholder.jpg') }}" src="{{ $user->getProfileImageUrl() }}" class="rounded-circle" alt="">
                    </a>
                    <input type="file" name="image" class="file-styled previewable hide">
                    <input type="hidden" name="_remove_image" value='' />
                </div>
                <div class="media-body text-center">
                    <h5 class="media-heading text-semibold">{{ trans('messages.upload_photo') }}</h5>
                    {{ trans('messages.photo_at_least', ["size" => "300px x 300px"]) }}
                    <br /><br />
                    <a href="#upload" onclick="$('input[name=image]').trigger('click')" class="btn btn-primary me-1"><span class="material-symbols-rounded">file_download</span> {{ trans('messages.upload') }}</a>
                    <a href="#remove" class="btn btn-secondary remove-profile-image"><span class="material-symbols-rounded">delete_outline</span> {{ trans('messages.remove') }}</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="sub_section">
            <h2 class="text-semibold text-primary">{{ trans('messages.user.information') }}</h2>

            @if (get_localization_config('show_last_name_first', Auth::user()->admin->getLanguageCode()))
                <div class="row">
                    <div class="col-md-6">
                        @include('helpers.form_control', [
                            'type' => 'text',
                            'name' => 'last_name',
                            'value' => $user->last_name,
                            'rules' => $user->rules()
                        ])
                    </div>
                    <div class="col-md-6">
                        @include('helpers.form_control', [
                            'type' => 'text',
                            'name' => 'first_name',
                            'value' => $user->first_name,
                            'rules' => $user->rules()
                        ])
                    </div>
                </div>
            @else 
                <div class="row">
                    <div class="col-md-6">
                        @include('helpers.form_control', [
                            'type' => 'text',
                            'name' => 'first_name',
                            'value' => $user->first_name,
                            'rules' => $user->rules()
                        ])
                    </div>
                    <div class="col-md-6">
                        @include('helpers.form_control', [
                            'type' => 'text',
                            'name' => 'last_name',
                            'value' => $user->last_name,
                            'rules' => $user->rules()
                        ])
                    </div>
                </div>
            @endif

            @include('helpers.form_control', [
                'type' => 'text',
                'name' => 'email',
                'value' => $user->email,
                'help_class' => 'profile',
                'rules' => $user->rules()
            ])

            <div class="form-group {{ $errors->has('phone') ? 'has-error' : '' }}">
                <label>
                    {{ trans('messages.phone') }}
                    @if (Acelle\Model\Setting::get('user.require_mobile_phone') == 'yes')
                        <span class="text-danger">*</span>
                    @endif
                </label>
                @include('helpers.form_control.phone', [
                    'name' => 'phone',
                    'value' => $user->phone,
                    'attributes' => Acelle\Model\Setting::get('user.require_mobile_phone') == 'yes' ? ['required' => 'required'] : [],
                ])
            </div>

            @include('helpers.form_control', [
                'type' => 'password',
                'label'=> trans('messages.new_password'),
                'name' => 'password',
                'rules' => $user->rules()
            ])

            @include('helpers.form_control', [
                'type' => 'password',
                'name' => 'password_confirmation',
                'rules' => $user->rules()
            ])

            <div class="mb-3">
                <label for="" class="form-label">{{ trans('messages.user.role') }}</label>
                <select name="role_uid" class="form-select {{ $errors->has('role_uid') ? 'is-invalid' : '' }}">
                    <option value="">{{ trans('messages.user.select_role') }}</option>
                    @foreach (Acelle\Model\Role::active()->get() as $role)
                        <option {{ $user->getRole() && $role->id == $user->getRole()->id ? 'selected' : '' }} value="{{ $role->uid }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

</div>
<hr>
<div class="text-left">
    <button class="btn btn-secondary me-2">{{ trans('messages.save') }}</button>
    <a href="{{ action('Admin\UserController@index', [
        'customer_uid' => $customer->uid,
    ]) }}" class="btn btn-link">{{ trans('messages.cancel') }}</a>
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