<div class="mb-4">
    <input type="hidden" name="options[type]" value="datetime" />

    <div class="form-group">
        <label>{{ trans('messages.days_of_month') }}<span class="text-danger">*</span></label>
        <div>
            <div class="btn-group day-month-select d-block" role="group" aria-label="Basic example">
                @for($i = 1; $i < 32; $i++)
                    <button role="button" class="btn btn-light mb-1">
                        {{ $i }}
                        <input
                            class="day-month-checkbox hide"
                            type="checkbox" name="options[days_of_month][]" value="{{ $i }}" />
                    </button>
                @endfor
            </div>
        </div>
    </div>

    <script>
        $('.day-month-select button').click(function(e) {
            e.preventDefault();

            if ($(this).find('.day-month-checkbox').is(':checked')) {
                $(this).find('.day-month-checkbox').prop('checked', false);
                $(this).removeClass('btn-primary');
                $(this).addClass('btn-light');
            } else {
                $(this).find('.day-month-checkbox').prop('checked', true);
                $(this).addClass('btn-primary');
                $(this).removeClass('btn-light');
            }
        });
    </script>

    @php
        $customer = Auth::user()->customer;

        $time = $customer->getCurrentTime()->format(config('custom.time_format'));
    @endphp
    
    @include('helpers.form_control', [
        'type' => 'time2',
        'name' => 'options[at]',
        'label' => trans('messages.automation.at'),
        'value' => $time,
        'help_class' => 'trigger'
    ])
    
    @if (config('custom.japan'))
        <input type="hidden" name="timezone" value="Asia/Tokyo" />
    @else
        @include('helpers.form_control', [
            'type' => 'select',
            'name' => 'timezone',
            'value' => Auth::user()->customer->timezone ?? config('app.timezone'),
            'options' => Tool::getTimezoneSelectOptions(),
            'include_blank' => trans('messages.choose'),
            'disabled' => true
        ])
    @endif
       

    @include('helpers.form_control', [
        'name' => 'mail_list_uid',
        'include_blank' => trans('messages.automation.choose_list'),
        'type' => 'select',
        'label' => trans('messages.list'),
        'value' => '',
        'options' => Auth::user()->customer->local()->readCache('MailListSelectOptions', []),
    ])

    <div class="automation-segment">

    </div>
</div>
