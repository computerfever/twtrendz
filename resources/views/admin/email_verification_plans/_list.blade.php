@if ($plans->count() > 0)
    <table class="table table-box pml-table mt-2"
        current-page="{{ empty(request()->page) ? 1 : empty(request()->page) }}"
    >
        @foreach ($plans as $key => $plan)
            <tr class="position-relative">
                <td width="1%" class="list-check-col">
                    <div class="text-nowrap d-flex align-items-canter">
                        @if (!$plan->visibility)
                            <a
                                title="{{ trans('messages.plan.show') }}"
                                link-method="POST"
                                href="{{ action('Admin\EmailVerificationPlanController@visibilityOn', $plan->uid) }}"
                                class="list-action-single plan-off"
                            >
                                <i class="material-symbols-rounded plan-off-icon fs-3 me-3">toggle_off</i>
                            </a>
                        @else
                            <a
                                title="{{ trans('messages.plan.hide') }}"
                                link-confirm="{{ trans('messages.plans.hide.confirm') }}"
                                link-method="POST"
                                href="{{ action('Admin\EmailVerificationPlanController@visibilityOff', $plan->uid) }}"
                                class="list-action-single plan-on"
                            >
                                <i class="material-symbols-rounded plan-on-icon fs-3 me-3">toggle_on</i>
                            </a>
                        @endif                        
                    </div>
                </td>

                <td>
                    <span class="no-margin stat-num">{{ Auth::user()->admin->formatDateTime($plan->created_at, 'datetime_full') }}</span>
                    <br>
                    <span class="text-muted2">{{ trans('messages.created_at') }}</span>
                </td>
                <td>
                    <h5 class="no-margin text-bold kq_search">
                        {{ \Acelle\Library\Tool::format_price($plan->getPrice(), $plan->currency->format) }}
                    </h5>
                    <span class="text-muted">{{ trans('messages.plan.price') }}</span>
                </td>
                <td>
                    <h5 class="no-margin text-bold kq_search">
                        {{ number_format($plan->credits) }}
                    </h5>
                    <span class="text-muted">{{ trans('messages.email_verification_plan.credits') }}</span>
                </td>
                <td class="text-end text-nowrap pe-0" width="5%">
                    <div class="list-actions">
                        <a href="{{ action('Admin\EmailVerificationPlanController@edit', $plan->uid) }}" role="button" class="btn btn-secondary btn-icon"> <span class="material-symbols-rounded">edit</span> {{ trans('messages.edit') }}</a>
                        <div class="btn-group">
                            <button role="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown"></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item list-action-single"
                                        link-method="POST"
                                        link-confirm-url="{{ action('Admin\EmailVerificationPlanController@deleteConfirm', ['uids' => $plan->uid]) }}"
                                        href="{{ action('Admin\EmailVerificationPlanController@delete', $plan->uid) }}"
                                        title="{{ trans('messages.email_verification_plan.delete') }}" class=""
                                    >
                                        <span class="material-symbols-rounded">delete</span> {{ trans('messages.email_verification_plan.delete') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </table>
    @include('elements/_per_page_select', ["items" => $plans])
    

    <script>
        var PlanList = {
			copyPopup: null,

			getCopyPopup: function() {
				if (this.copyPopup === null) {
					this.copyPopup = new Popup();
				}

				return this.copyPopup;
			}
		}

        $(function() {
            $('.copy-plan-link').on('click', function(e) {
                e.preventDefault();			
                var url = $(this).attr('href');

                PlanList.getCopyPopup().load({
                    url: url
                });
            });

            $('.cant_show').click(function(e) {
                e.preventDefault();

                var confirm = `{{ trans('messages.plan.cant_show') }}`;
                var dialog = new Dialog('alert', {
                    message: confirm
                })
            });

            $('.enable-plan').click(function(e) {
                e.preventDefault();

                var confirm = `{{ trans('messages.plan.enable_and_visible.confirm') }}`;
                var href_yes = $(this).attr('href_yes');
                var href_no = $(this).attr('href_no');

                var dialog = new Dialog('yesno', {
                    message: confirm,
                    no: function(dialog) {
                        $.ajax({
                            url: href_no,
                            method: 'POST',
                            data: {
                                _token: CSRF_TOKEN,
                            },
                            statusCode: {
                                // validate error
                                400: function (res) {
                                    alert('Something went wrong!');
                                }
                            },
                            success: function (response) {
                                // notify
                                notify({
        type: 'success',
        title: '{!! trans('messages.notify.success') !!}',
        message: response.message
    });
                            }
                        });
                    },
                    yes: function(dialog) {                    
                        $.ajax({
                            url: href_yes,
                            method: 'POST',
                            data: {
                                _token: CSRF_TOKEN,
                            },
                            statusCode: {
                                // validate error
                                400: function (res) {
                                    alert('Something went wrong!');
                                }
                            },
                            success: function (response) {
                                // notify
                                notify({
        type: 'success',
        title: '{!! trans('messages.notify.success') !!}',
        message: response.message
    });
                            }
                        });
                    },
                });
            });
        });
    </script>
@elseif (!empty(request()->keyword))
    <div class="empty-list">
        <i class="material-symbols-rounded">assignment_turned_in</i>
        <span class="line-1">
            {{ trans('messages.no_search_result') }}
        </span>
    </div>
@else
    <div class="empty-list">
        <i class="material-symbols-rounded">assignment_turned_in</i>
        <span class="line-1">
            {{ trans('messages.plan_empty_line_1') }}
        </span>
    </div>
@endif
