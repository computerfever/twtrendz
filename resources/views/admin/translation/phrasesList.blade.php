    <table class="table table-box table-box-head field-list phrase-list">
        <thead>
            <th width="1%" class="ps-0"></th>
            <th width="20%" class="py-3 fw-600">{{ trans('messages.translation.key') }}</th>
            <th width="80%" class="py-3 fw-600">{{ trans('messages.translation.japanese') }}</th>
        </thead>

        <tbody>
            @foreach ($phrases as $phrase)
                <tr>
                    <td class="ps-0">
                        <label>
                            <input type="checkbox" class="node styled"
                                   name="uids[]"
                                   value="{{ $phrase->uid }}"
                            />
                        </label>
                    </td>
                    <td>
                        <code style="font-size:12px;" class="mb-3 text-muted2 text-dark">{{ $phrase->key }}</code>
                        <p class="mb-0">{{ trans($phrase->key) }}</p>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="w-100 pe-5">
                                <input data-control="phrase-input" type="text" name="phrases[{{ $phrase->key }}]" value="{{ $phrase->ja }}"
                                    placeholder="{{ trans('messages.translation.enter_phrase') }}"
                                    class="inline-editable mode-view w-100"
                                />
                            </div>
                            <div class="inine-actions text-nowrap">
                                <button data-control="done-button" data-url="{{ action('Admin\TranslationController@phrasesWrite', [
                                    'uid' => $phrase->uid,
                                ]) }}" class="btn btn-secondary">{{ trans('messages.translation.done') }}</button>
                                <button data-control="expand" data-key="{{ $phrase->key }}" class="btn btn-light">{{ trans('messages.translation.expand') }}</button>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @include('elements/_per_page_select', ["items" => $phrases])

    <script>
        $(function() {
            $('[data-control="expand"]').each(function() {
                new Expander($(this));
            })
        });

        var Expander = class {
            constructor(button) {
                this.button = button;
                this.key = this.button.attr('data-key');
                this.input = $('[name="phrases[' + this.key + ']"]');
                this.popup = new Popup();
                
                // events
                this.button.on('click', (e) => {
                    e.preventDefault();

                    window.translationManager.unselectAll();
                    window.translationManager.focus(this.input);

                    var div = $('<div>').html(`
                        <div class="modal-dialog shadow modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-3">
                                    <div class="form-group">
                                        <label class="fw-600">` + this.key + `</label>
                                        <textarea class="form-control">` + this.input.val() + `</textarea>
                                    </div>
                                    <div>
                                        <button data-control="expand-save" class="btn btn-secondary">{{ trans('messages.translation.ok') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>    
                    `);

                    this.popup.loadHtml(div);

                    this.saveButton = div.find('[data-control="expand-save"]');

                    this.textarea = div.find('textarea');
                    this.textarea.focus();

                    this.saveButton.on('click', (e) => {
                        e.preventDefault();

                        // copy to input
                        this.input.val(this.textarea.val());

                        window.translationManager.autoSave(() => {
                        //     window.translationManager.unselectAll();
                        //     window.translationManager.focus(this.input);
                        });

                        //
                        this.popup.hide();
                    });
                })
            }
        }
    </script>