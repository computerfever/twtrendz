<div class="row">
	<div class="col-md-6">
		<div class="sub_section">
            <div class="form-group {{ $errors->has('name') ? ' has-error' : '' }}">
                <label class="form-label">{{ trans('messages.role.name') }} <span class="text-danger">*</span></label>
                <input {{ Auth::user()->customer->can('update', $role) ? '' : 'disabled' }} type="text" name="name" value="{{ $role->name }}" class="form-control">
                @if ($errors->has('name'))
                    <span class="help-block">
                        {{ $errors->first('name') }}
                    </span>
                @endif
            </div>

            <div class="form-group {{ $errors->has('description') ? ' has-error' : '' }}">
                <label class="form-label">{{ trans('messages.role.description') }} <span class="text-danger">*</span></label>
                <textarea {{ Auth::user()->customer->can('update', $role) ? '' : 'disabled' }} type="text" name="description" class="form-control">{{ $role->description }}</textarea>
                @if ($errors->has('description'))
                    <span class="help-block">
                        {{ $errors->first('description') }}
                    </span>
                @endif
            </div>
		</div>

        <h3>{{ trans('messages.permissions') }}</h3>

        <div class="sub_section">
            @foreach (groupPermissionsByPrefix(config('permissions')) as $group => $permissions)
                <h5 class="mt-5 mb-0">{{ trans('permission.group.' . $group) }}</h5>

                <div select-one="container">
                    @foreach ($permissions as $permission)
                        <div class="ps-2">
                            <label class="form-check-label d-flex py-3 border-bottom" for="{{ $permission }}">
                                <span class="me-3">
                                    <label class="checker">
                                        <input select-one="checkbox" {{ Auth::user()->customer->can('update', $role) ? '' : 'disabled' }} type="checkbox" name="permissions[]" value="{{ $permission }}" class="styled4" id="{{ $permission }}" {{ $role->hasPermission($permission) ? 'checked' : '' }}>
                                        <span class="checker-symbol"></span>
                                    </label>
                                </span>
                                <span>
                                    <span class="d-block fw-semibold">{{ trans('permission.' . $permission . '.title') }}</span>
                                    <span class="d-block">{{ trans('permission.' . $permission . '.desc') }}</span>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            @endforeach

            <script>
                $(() => {
                    $('[select-one="container"]').each(function() {
                        new SelectOneOnly($(this).find('[select-one="checkbox"]'));
                    });
                    
                });

                var SelectOneOnly = class {
                    constructor(checkboxes) {
                        this.checkboxes = checkboxes;

                        this.events();
                    }

                    events() {
                        var _this = this;
                        this.checkboxes.on('change', function() {
                            var checkbox = $(this);

                            if (checkbox.is(':checked')) {
                                _this.select(checkbox);
                            }
                        });
                    }

                    select(checkbox) {
                        this.checkboxes.each(function() {
                            var c = $(this);

                            if(checkbox.attr('value') !== c.attr('value')) {
                                c.prop('checked', false);
                            }
                        });
                    }
                }
            </script>
        </div>
        
        @if (Auth::user()->customer->can('update', $role))
            <div class="text-left">
                <button class="btn btn-primary me-1"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
                <a href="{{ action("RoleController@index") }}" class="btn btn-secondary"><i class="icon-cross"></i> {{ trans('messages.cancel') }}</a>
            </div>
        @endif
	</div>
</div>
