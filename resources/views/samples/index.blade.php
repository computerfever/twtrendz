@extends('layouts.core.frontend', [
    'menu' => false,
])
@section('title', 'Samplel UI')
@section('page_header')
    <!-- nothing here -->
@endsection

@section('content')
    <div class="row">
        <div class="col-md-10">
            <h1>Notifications</h1>
            <div class="alert alert-info"
                style="display: flex; flex-direction: row; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-direction: row; align-items: center;">
                    <div style="margin-right:15px">
                        <i class="lnr lnr-bubble"></i>
                    </div>
                    <div style="padding-right: 40px">
                        <h4>Pending verification...</h4>
                        <p>We have sent an email to this sender's email address. Please click on the link included in the
                            email to activate</p>
                    </div>
                </div>
                <button class="btn btn-secondary">Send again!</button>
            </div>

            <div class="alert alert-warning"
                style="display: flex; flex-direction: row; align-items: center; justify-content: space-between;">
                <div style="display: flex; flex-direction: row; align-items: center;">
                    <div style="margin-right:15px">
                        <span class="material-symbols-rounded">error_outline</span>
                    </div>
                    <div style="padding-right: 40px">
                        <h4>Verification required</h4>
                        <p>We have sent an email to this sender's email address. Please click on the link included in the
                            email to activate</p>
                    </div>
                </div>
                <button class="btn btn-secondary">{{ trans('messages.sending_domain.verify') }}</button>
            </div>

            <div class="alert alert-danger"
                style="display: flex; flex-direction: row; align-items: center; justify-content: space-between;">
                <div style="display: flex; flex-direction: row; align-items: center;">
                    <div style="margin-right:15px">
                        <i class="lnr lnr-circle-minus"></i>
                    </div>
                    <div style="padding-right: 40px">
                        <h4>Verification required</h4>
                        <p>We have sent an email to this sender's email address. Please click on the link included in the
                            email to activate</p>
                    </div>
                </div>
                <button class="btn btn-secondary">{{ trans('messages.sending_domain.verify') }}</button>
            </div>

            <div class="alert alert-success"
                style="display: flex; flex-direction: row; align-items: center; justify-content: space-between;">
                <div style="display: flex; flex-direction: row; align-items: center;">
                    <div style="margin-right:15px">
                        <i class="lnr lnr-checkmark-circle"></i>
                    </div>
                    <div style="padding-right: 40px">
                        <h4>Sender verified</h4>
                        <p>We have sent an email to this sender's email address. Please click on the link included in the
                            email to activate</p>
                    </div>
                </div>
            </div>

            <h2>Form Input</h2>
            <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }} control-text">
                <label>
                    Text Input
                    <span class="text-danger">*</span>
                </label>
                <input id="name" placeholder="" value="" type="text" name="name" class="form-control required" />
    
                @if ($errors->has('name'))
                    <span class="help-block">
                        {{ $errors->first('name') }}
                    </span>
                @endif
            </div>

            <div class="form-group">
                <label class="form-label mb-0">
                    <span class="form-label d-flex align-items-top">
                        <label class="d-block mb-0 me-3">
                            <input type="hidden" name="plan[general][no_payment_required_when_free]" value="0">
                            <input {{ true ? 'checked' : '' }} type="checkbox" class="styled me-2 numeric"
                                name="plan[general][no_payment_required_when_free]" value="1" />
                            <span class="check-symbol"></span>
                        </label>
                        <div>
                            <span class="fw-semibold">{{ trans('messages.signature.make_default') }}</span>
                            <p class="mb-0">{{ trans('messages.signature.make_default.desc') }}</p>
                        </div>
                    </span>
                </label>
            </div>

            <div class="form-group">
                <label class="form-label mb-0">
                    <span class="form-label d-flex align-items-top">
                        <label class="d-block mb-0 me-3">
                            <input type="hidden" name="plan[general][no_payment_required_when_free]" value="0">
                            <input {{ true ? 'checked' : '' }} type="checkbox" class="styled me-2 numeric"
                                name="plan[general][no_payment_required_when_free]" value="1" />
                            <span class="check-symbol"></span>
                        </label>
                        <div>
                            <span class="">{{ trans('messages.signature.make_default') }}</span>
                        </div>
                    </span>
                </label>
            </div>

            <div class="form-group">
                <label for="" class="form-label fw-semibold">Radios</label>
                <div class="d-flex align-items-center">
                    <div class="d-flex align-items-center me-4">
                        <div class="me-2">
                            <input type="radio" name="webhook[method]" value="get" id="method-get" class="styled" />
                            <label class="check-symbol" for="method-get"></label>
                        </div>
                        <div>
                            <label for="method-get" class="mb-0">{{ trans('messages.automation.outgoing_webhook.get_method') }}</label>
                        </div>
                    </div>
                    <div class="d-flex align-items-center me-4">
                        <div class="me-2">
                            <input type="radio" name="webhook[method]" value="post" id="method-post" class="styled" />
                            <label class="check-symbol" for="method-post"></label>
                        </div>
                        <label for="method-post" class="mb-0">{{ trans('messages.automation.outgoing_webhook.post_method') }}</label>
                    </div>
                    <div class="d-flex align-items-center me-4">
                        <div class="me-2">
                            <input type="radio" name="webhook[method]" value="put" id="method-put" class="styled" />
                            <label class="check-symbol" for="method-put"></label>
                        </div>
                        <label for="method-put" class="mb-0">{{ trans('messages.automation.outgoing_webhook.put_method') }}</label>
                    </div>
                    <div class="d-flex align-items-center me-4">
                        <div class="me-2">
                            <input type="radio" name="webhook[method]" value="delete" id="method-delete" class="styled" />
                            <label class="check-symbol" for="method-delete"></label>
                        </div>
                        <label for="method-delete" class="mb-0">{{ trans('messages.automation.outgoing_webhook.delete_method') }}</label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="fw-semibold mb-2">{{ trans('messages.site.type') }}</label>
                <div>
                    <div data-control="option-container" class="d-flex">
                        <label class="me-3">
                            <input type="radio" name="site[type]" value="1" id="udesfe_true" class="styled"><span class="check-symbol"></span>
                        </label>
                        <div>
                            <label for="udesfe_true" class="mb-0 radio-label">{{ trans('messages.site.type.landing_page') }}</label>
                            <p class="text-muted mb-2 small fst-italic">
                                {{ trans('messages.site.type.landing_page.wording') }}
                            </p>
                        </div>
                    </div>
                    <div data-control="option-container" class="d-flex mt-2">
                        <label class="me-3">
                            <input checked="" type="radio" name="site[type]" value="0" id="udesfe_false" class="styled"><span class="check-symbol"></span>
                        </label>
                        <div>
                            <label for="udesfe_false" class="mb-0 radio-label">{{ trans('messages.site.type.website') }}</label>
                            <p class="text-muted mb-2 small fst-italic">
                                {{ trans('messages.site.type.website.wording') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="fw-semibold">Control: Select</label>
                
                @include('helpers.form_control.select', [
                    'name' => 'name',
                    'value' => 2,
                    'options' => [
                        ['value' => null, 'text' => 'Select none'],
                        ['value' => 2, 'text' => 'Title #1|||Description #1'],
                        ['value' => 3, 'text' => 'Title #2|||Description #2'],
                    ],
                    'attributes' => [
                        'required' => 'required',
                    ],
                ])
            </div>

            <div class="form-group">
                <label>
                    {{ trans('messages.phone') }}
                </label>
                @include('helpers.form_control.phone', [
                    'name' => 'phone',
                    'value' => '',
                ])
            </div>
        </div>
    </div>
@endsection
