@extends('layouts.core.backend', [
    'menu' => 'translation',
])

@section('title', trans('messages.translation.phrases'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
        </ul>
        <h1>
            <span class="text-semibold"><span class="material-symbols-rounded">format_list_bulleted</span> {{ trans('messages.translation') }}</span>
        </h1>
    </div>

@endsection

@section('content')

<div class="row">
    <div class="col-sm-12 col-md-10 col-lg-10">
        <p>{!! trans('messages.translation.intro') !!}</p>
    </div>
</div>

<form data-control="translation-form" method="POST" action="{{ action('Admin\TranslationController@phrasesSave') }}">
    @csrf

    <div id="TranslationContainer" class="listing-form"
        per-page="1"
    >
        <div class="d-flex top-list-controls top-sticky-content">
            <div class="me-auto">
                <div class="filter-box">
                    <div class="checkbox inline check_all_list">
                        <div class="mr-2">
							@include('helpers.select_tool', [
								'disable_all_items' => false
							])
						</div>
                    </div>
                    <div class="dropdown list_actions" style="display: none">
                        <button role="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            {{ trans('messages.actions') }} <span class="number"></span><span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" link-method="POST" link-confirm="{{ trans('messages.translation.finish_write.confirm') }}"
                                    href="{{ action('Admin\TranslationController@phrasesFinishWrite') }}">
                                    <span class="material-symbols-rounded">delete_outline</span> {{ trans('messages.translation.finish_write') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <span class="filter-group">
                        <span class="title text-semibold text-muted">{{ trans('messages.sort_by') }}</span>
                        <select class="select" name="sort_order">
                            <option value="translation_phrases.key">{{ trans('messages.translation.key') }}</option>
                            <option value="translation_phrases.created_at">{{ trans('messages.created_at') }}</option>
                        </select>
                        <input type="hidden" name="sort_direction" value="desc" />
                                            <button class="btn btn-xs sort-direction" rel="asc" data-popup="tooltip" title="{{ trans('messages.change_sort_direction') }}" role="button" class="btn btn-xs">
                            <span class="material-symbols-rounded desc">sort</span>
                        </button>
                    </span>
                    <span class="text-nowrap">
                        <input type="text" name="keyword" class="form-control search" value="{{ request()->keyword }}" placeholder="{{ trans('messages.type_to_search') }}" />
                        <span class="material-symbols-rounded">search</span>
                    </span>
                </div>
            </div>
            <div class="text-end">
                <div class="">
                    <div class="d-flex justify-content-end">
                        <button id="TranslationRefresh" data-control="refresh" type="button" class="btn btn-light">
                            {{ trans('messages.translation.refresh_load_new_phrases') }}
                        </button>
                        <button id="TranslationSaveWrite" data-control="save" type="button" class="btn btn-secondary ms-2">
                            {{ trans('messages.translation.finish_write') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="TranslationContent" class="pml-table-container"></div>
    </div>
</form>

<script>
    $(document).ready(function() {
        TranslationIndex.getList().load();

        //
        window.translationManager = new TranslationManager($('[data-control="translation-form"]'));
    });

    var TranslationIndex = {
        getList: function() {
            return makeList({
                url: '{{ action('Admin\TranslationController@phrasesList') }}',
                container: $('#TranslationContainer'),
                content: $('#TranslationContent'),
                loaded: function() {
                    window.translationManager.listEvents();

                    // focus first one
                    // window.translationManager.focus(window.translationManager.getInputs().first());
                }
            });
        }
    };

    var TranslationManager = class {
        constructor(form) {
            this.form = form;
            this.currentFocus = null;
            this.currentSelect = null;

            this.events();
        }

        getSaveUrl() {
            return this.form.attr('action');
        }

        getSaveButton() {
            return this.form.find('[data-control="save"]');
        }

        getRefreshButton() {
            return this.form.find('[data-control="refresh"]');
        }

        getInputs() {
            return this.form.find('[data-control="phrase-input"]');
        }

        getDoneButtons() {
            return this.form.find('[data-control="done-button"]');
        }

        getCancelButtons() {
            return this.form.find('[data-control="cancel-button"]');
        }

        events() {
            var _this = this;

            // submit
            this.form.on('submit', (e) => {
                e.preventDefault();
            });

            // click save
            this.getSaveButton().on('click', (e) => {
                this.save();
            });

            // click refresh
            this.getRefreshButton().on('click', (e) => {
                TranslationIndex.getList().load();
            });

            $(document).on('keydown', (event) => {
                if (!this.currentFocus) {
                    return;
                }

                switch(event.key) {
                    case 'Enter':
                        if ($('[data-control="expand-save"]').is(':visible')) {
                            return;
                        }
                        this.select(this.currentFocus);
                        break;
                    case 'ArrowDown':
                    case 'Tab':
                        event.preventDefault(); // Prevent default behavior
                        
                        this.focusNext();
                        break;
                    case 'ArrowUp':
                        event.preventDefault(); // Prevent default behavior

                        this.focusPrevious();
                        
                        break;
                }
            });
        }

        focusNext() {
            if (this.currentSelect) {
                return;
            }

            var inputFields = this.getInputs();
            const currentInput = this.currentFocus;
            const currentIndex = inputFields.index(currentInput);

            if (currentIndex < inputFields.length - 1) {
                this.focus(inputFields.eq(currentIndex + 1));
            } else {
                // If at the last input, move focus to the first input
                this.focus(inputFields.eq(0));
            }
        }

        focusPrevious() {
            if (this.currentSelect) {
                return;
            }

            var inputFields = this.getInputs();
            const currentInput = this.currentFocus;
            const currentIndex = inputFields.index(currentInput);

            if (currentIndex > 0) {
                this.focus(inputFields.eq(currentIndex - 1));
            } else {
                // If at the first input, move focus to the last input
                this.focus(inputFields.eq(inputFields.length - 1));
            }
        }

        selectNext() {
            var inputFields = this.getInputs();
            const currentInput = this.currentSelect;
            const currentIndex = inputFields.index(currentInput);

            if (currentIndex < inputFields.length - 1) {
                this.select(inputFields.eq(currentIndex + 1));
            } else {
                // If at the last input, move focus to the first input
                this.select(inputFields.eq(0));
            }
        }

        selectPrevious() {
            var inputFields = this.getInputs();
            const currentInput = this.currentSelect;
            const currentIndex = inputFields.index(currentInput);

            if (currentIndex > 0) {
                this.select(inputFields.eq(currentIndex - 1));
            } else {
                // If at the first input, move focus to the last input
                this.select(inputFields.eq(inputFields.length - 1));
            }
        }

        listEvents() {
            var _this = this;

            // input change
            this.getInputs().on('change', (e) => {
                // this.autoSave();
            });

            // done click
            this.getDoneButtons().on('click', (e) => {
                e.preventDefault();

                var url = $(e.target).attr('data-url');
                
                this.autoSave(() => {
                    this.unselectAll();
                });
            });

            // cancel click
            this.getCancelButtons().on('click', (e) => {
                this.unselectAll();
            });

            // input select
            this.getInputs().on('click', function(e) {
                _this.focus($(this));
                _this.select($(this));
            });

            var inputFields = this.getInputs();
            inputFields.on('keydown', function(event) {
                // const currentInput = $(this);
                // const currentIndex = inputFields.index(currentInput);
                
                switch(event.key) {
                    case 'Enter':
                        event.preventDefault(); // Prevent default behavior
                        _this.autoSave(function() {
                            _this.unselectAll();
                        });
                        break;
                    case 'ArrowDown':
                    case 'Tab':
                        event.preventDefault(); // Prevent default behavior
                        
                        _this.selectNext();

                        break;
                    case 'ArrowUp':
                        event.preventDefault(); // Prevent default behavior
                        
                        _this.selectPrevious();
                        break;
                }
            });
        }

        unselectAll() {
            // unselect other
            this.getInputs().addClass('mode-view');
            this.getInputs().blur();

            //
            this.currentSelect = null;
        }

        select(input) {
            this.unselectAll();

            // select item
            input.removeClass('mode-view');
            input.focus();

            //
            this.currentSelect = input;

            //
            this.focus(input);
        }

        unfocusAll() {
            // unselect other
            this.getInputs().removeClass('mode-focus');

            // 
            this.currentFocus = null;
        }

        focus(input) {
            this.unfocusAll();

            // select item
            input.addClass('mode-focus');

            // 
            this.currentFocus = input;
        }

        save() {
            addMaskLoading();

            $.ajax({
                url: this.getSaveUrl(),
                type: 'POST',
                data: this.form.serialize(),
            }).done((response) => {
                // notify
                notify({
                    type: 'success',
                    message: response.message
                });

                removeMaskLoading();
            }).fail((response) => {
            });
        }

        write(url) {
            addMaskLoading();

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                },
            }).done((response) => {
                // notify
                notify({
                    type: 'success',
                    message: response.message
                });

                removeMaskLoading();

                // load list
                TranslationIndex.getList().load();
            }).fail((response) => {
            });
        }

        autoSave(callback) {
            $.ajax({
                url: this.getSaveUrl(),
                type: 'POST',
                data: this.form.serialize(),
            }).done((response) => {
                // notify
                notify({
                    type: 'success',
                    message: '{{ trans('messages.translation.auto_aved') }}'
                });

                // 
                if (callback) {
                    callback();
                }
            }).fail((response) => {
            });
        }
    }
</script>

@endsection
