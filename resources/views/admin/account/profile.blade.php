@extends('layouts.core.backend', [
    'menu' => 'profile',
])

@section('title', trans('messages.my_profile'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item active">{{ trans('messages.profile') }}</li>
        </ul>
        <h1>
            <span class="text-semibold"><span class="material-symbols-rounded">person_outline</span> {{ $admin->displayName() }}</span>
        </h1>
    </div>

@endsection

@section('content')

    @include("admin.account._menu", [
        'menu' => 'profile',
    ])

    <form enctype="multipart/form-data" action="{{ action('Admin\AccountController@profile') }}" method="POST" class="form-validaate-jquery">
        {{ csrf_field() }}

        <div class="row">
            <div class="col-md-3">
                <div class="sub_section">
                    <h3 class="text-semibold text-primary mb-4">{{ trans('messages.profile_photo') }}</h3>
                    <div class="media profile-image">
                        <div class="media-left">
                            <a href="#" class="upload-media-container" onclick="$('input[name=image]').trigger('click')">
                                <img preview-for="image" empty-src="{{ AppUrl::asset('images/placeholder.jpg') }}" src="{{ $user->getProfileImageUrl() }}" class="rounded-circle" alt="">
                            </a>
                            <input type="file" name="image" class="file-styled previewable hide" accept="image/*">
                            <input type="hidden" name="_remove_image" value='' />
                        </div>
                        <div class="media-body text-center">
                            <h5 class="media-heading text-semibold mt-2">{{ trans('messages.upload_your_photo') }}</h5>
                            <p class="mb-0">{{ trans('messages.photo_at_least', ["size" => "300px x 300px"]) }}</p>
                            <p>{!! trans('messages.image.upload_help', [ 'max' => \Acelle\Library\Tool::maxFileUploadInBytes()]) !!}</p>
                            <a href="#upload" onclick="$('input[name=image]').trigger('click')" class="btn btn-primary me-1"><span class="material-symbols-rounded">file_download</span> {{ trans('messages.upload') }}</a>
                            <a href="#remove" class="btn btn-secondary remove-profile-image"><span class="material-symbols-rounded">delete_outline</span> {{ trans('messages.remove') }}</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="sub_section">
                    <h3 class="text-semibold text-primary mb-4">{{ trans('messages.basic_information') }}</h3>

                    @if (get_localization_config('show_last_name_first', $admin->getLanguageCode()))
                        <div class="row">
                            <div class="col-md-6">
                                @include('helpers.form_control', ['type' => 'text', 'name' => 'last_name', 'value' => $user->last_name, 'rules' => $user->rules()])
                            </div>
                            <div class="col-md-6">
                                @include('helpers.form_control', ['type' => 'text', 'name' => 'first_name', 'value' => $user->first_name, 'rules' => $user->rules()])
                            </div>
                        </div>
                    @else 
                        <div class="row">
                            <div class="col-md-6">
                                @include('helpers.form_control', ['type' => 'text', 'name' => 'first_name', 'value' => $user->first_name, 'rules' => $user->rules()])
                            </div>
                            <div class="col-md-6">
                                @include('helpers.form_control', ['type' => 'text', 'name' => 'last_name', 'value' => $user->last_name, 'rules' => $user->rules()])
                            </div>
                        </div>
                    @endif

                    
                    @if (config('custom.japan'))
                        <input type="hidden" name="timezone" value="Asia/Tokyo" />
                    @else
                        @include('helpers.form_control', [
                            'type' => 'select',
                            'name' => 'timezone',
                            'value' => $admin->timezone ?? config('app.timezone'),
                            'options' => Tool::getTimezoneSelectOptions(),
                            'include_blank' => trans('messages.choose'),
                            'rules' => $user->rules()
                        ])
                    @endif
                    

                    @include('helpers.form_control', [
                        'type' => 'select',
                        'name' => 'language_id',
                        'label' => trans('messages.language'),
                        'value' => $admin->language_id ?? \Acelle\Model\Language::getDefaultLanguage()->id,
                        'options' => Acelle\Model\Language::getSelectOptions(),
                        'include_blank' => trans('messages.choose'),
                        'rules' => $user->rules()
                    ])

                    <h3 class="text-semibold text-primary mb-4 mt-5">{{ trans('messages.account.personality') }}</h3>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label class="mb-2">{{ trans('messages.account.menu_layout') }}</label>
                            @include('layouts.core._menu_layout_switch', [
                                'menu_layout' => $admin->menu_layout,
                            ])
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <label class="mb-3">{{ trans('messages.theme_mode') }}</label>
                            @include('layouts.core._theme_mode_control', [
                                'theme_mode' => $admin->theme_mode,
                            ])
                        </div>  
                    </div>

                    <div class="row">
                        <div class="col-md-12 color-box">
                            <label class="mb-3">{{ trans('messages.color_scheme') }}</label>
                            <div class="text-left mb-4 profile-scheme-select mt-2">
                                @include('layouts.core._theme_color_control', [
                                    'theme_color' => Auth::user()->admin->getColorScheme(),
                                ])  
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-10 color-box">
                            @include('helpers.form_control', [
                                'type' => 'select',
                                'class' => '',
                                'name' => 'text_direction',
                                'value' => $admin->text_direction,
                                'help_class' => 'admin',
                                'options' => [
                                    ['text' => trans('messages.text_direction.ltr'), 'value' => 'ltr'],
                                    ['text' => trans('messages.text_direction.rtl'), 'value' => 'rtl']
                                ],
                                'rules' => '',
                            ])
                        </div>
                    </div>

                </div>
            </div>
            <div class="col-md-4">
                <div class="sub_section">
                    <h3 class="text-semibold text-primary mb-4">{{ trans('messages.account') }}</h3>

                    @include('helpers.form_control', ['type' => 'text', 'name' => 'email', 'value' => $user->email, 'help_class' => 'profile', 'rules' => $user->rules()])

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

                    @include('helpers.form_control', ['type' => 'password', 'label'=> trans('messages.new_password'), 'name' => 'password', 'rules' => $user->rules()])

                    @include('helpers.form_control', ['type' => 'password', 'name' => 'password_confirmation', 'rules' => $user->rules()])

                </div>
            </div>
        </div>
        <hr>
        <div class="text-left">
            <button class="btn btn-secondary"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
        </div>

    <form>

    <script>
        function changeSelectColor() {
            $('.select2 .select2-selection__rendered, .select2-results__option').each(function() {
                var text = $(this).html();
                if (text == '{{ trans('messages.default') }}') {
                    if($(this).find("i").length == 0) {
                        $(this).prepend("<i class='material-symbols-rounded text-teal-600 theme-option me-2'>padding</i>");
                    }
                }
                if (text == '{{ trans('messages.blue') }}') {
                    if($(this).find("i").length == 0) {
                        $(this).prepend("<i class='material-symbols-rounded text-blue theme-option me-2'>padding</i>");
                    }
                }
                if (text == '{{ trans('messages.green') }}') {
                    if($(this).find("i").length == 0) {
                        $(this).prepend("<i class='material-symbols-rounded text-green theme-option me-2'>padding</i>");
                    }
                }
                if (text == '{{ trans('messages.brown') }}') {
                    if($(this).find("i").length == 0) {
                        $(this).prepend("<i class='material-symbols-rounded text-brown theme-option me-2'>padding</i>");
                    }
                }
                if (text == '{{ trans('messages.pink') }}') {
                    if($(this).find("i").length == 0) {
                        $(this).prepend("<i class='material-symbols-rounded text-pink theme-option me-2'>padding</i>");
                    }
                }
                if (text == '{{ trans('messages.grey') }}') {
                    if($(this).find("i").length == 0) {
                        $(this).prepend("<i class='material-symbols-rounded text-grey theme-option me-2'>padding</i>");
                    }
                }
                if (text == '{{ trans('messages.white') }}') {
                    if($(this).find("i").length == 0) {
                        $(this).prepend("<i class='material-symbols-rounded text-white theme-option me-2'>padding</i>");
                    }
                }
            });
        }

        $(document).ready(function() {
            setInterval("changeSelectColor()", 100);

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

            // change color effects
            $('.color-scheme-select').on('click', function(e) {
                var value = $(this).val();
                $("body").removeClass (function (index, className) {
                    return (className.match (/(^|\s)theme-\S+/g) || []).join(' ');
                });
                $('body').addClass('theme-' + value);

            includeCss('{{ AppUrl::asset('core/css/theme') }}/' + value + '.css');

                detectLogo();
            });
        });
    </script>

@endsection
