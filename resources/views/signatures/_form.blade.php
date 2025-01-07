{{ csrf_field() }}

<div class="row">
	<div class="col-sm-12 col-md-12">
		<div class="form-group {{ $errors->has('name') ? 'has-error' : '' }} control-text">
			<label class="fw-semibold">
				{{ trans('messages.signature.signature_name') }}
				<span class="text-danger">*</span>
			</label>
			<input id="name" placeholder="" value="{{ $signature->name }}" type="text" name="name" class="form-control required" />

			@if ($errors->has('name'))
				<span class="help-block">
					{{ $errors->first('name') }}
				</span>
			@endif
		</div>

		<div id="SignatureEditorContainer">
			<div class="form-group {{ $errors->has('content') ? 'has-error' : '' }} control-text">
				<div class="d-flex align-items-center mb-2">
					<label class="fw-semibold">
						{{ trans('messages.signature.content') }}
						<span class="text-danger">*</span>
					</label>

					<div class="ms-auto text-end">
						<div class="btn-group" role="group" aria-label="Basic example">
							<button data-control="show-form" type="button" class="btn btn-light active">{{ trans('messages.signature.source_code') }}</button>
							<button data-control="show-preview" type="button" class="btn btn-light">{{ trans('messages.signature.preview') }}</button>
						</div>
					</div>
				</div>

				<div data-control="form">
					<div id="editor" class="rounded shadow-sm" style="height: 400px;width: 100%;"></div>
						
					<script src="/ace/src-noconflict/ace.js" type="text/javascript" charset="utf-8"></script>
					<textarea id="signatureContent" type="text" name="content" class="form-control required template-editor" style="display: none;">{!! $signature->content !!}</textarea>
					<script>
						var editor = ace.edit("editor");
						editor.setTheme("ace/theme/monokai");
						editor.getSession().setMode("ace/mode/html"); // Optional: set to HTML mode

						// Set the content, using Blade to escape it for raw HTML
						var content = `{!! addslashes($signature->content) !!}`;
						editor.setValue(content);

						// Set up a listener for changes
						editor.getSession().on('change', function(delta) {
							// This code will run every time the content changes
							var content = editor.getValue();
							document.getElementById('signatureContent').value = editor.getValue();
							// You can perform additional actions here, like saving or validating the content
						});
					</script>
					
					{{-- <script>
						var editor;
						$(document).ready(function() {
							editor = tinymce.init({
								language: '{{ Auth::user()->admin->getLanguageCode() }}',
								selector: '.template-editor',
								directionality: "{{ Auth::user()->admin->text_direction }}",
								height: 500,
								relative_urls : false,
								remove_script_host : false,
								document_base_url : '{{ url('/') }}',
								skin: "oxide",
								forced_root_block: "",
								plugins: 'fullpage print preview paste importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern noneditable help charmap quickbars emoticons',
								imagetools_cors_hosts: ['picsum.photos'],
								menubar: 'file edit view insert format tools table help',
								toolbar: [
									'ltr rtl | acelletags | undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify',
									'outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | ltr rtl'
								],
								toolbar_location: 'top',
								menubar: true,
								statusbar: false,
								toolbar_sticky: true,
								valid_elements : '*[*],meta[*]',
								valid_children: '+p[ol],+p[ul],+h1[div],+h2[div],+h3[div],+h4[div],+h5[div],+h6[div],+a[div],*[*]',
								extended_valid_elements : "meta[*]",
								valid_children : "+body[style],+body[meta],+div[h2|span|meta|object],+object[param|embed]",
								external_filemanager_path:APP_URL.replace('/index.php','')+"/filemanager2/",
								filemanager_title:"Responsive Filemanager" ,
								external_plugins: { "filemanager" : APP_URL.replace('/index.php','')+"/filemanager2/plugin.min.js"},
								setup: function (editor) {
									
									/* Menu button that has a simple "insert date" menu item, and a submenu containing other formats. */
									/* Clicking the first menu item or one of the submenu items inserts the date in the selected format. */
									editor.ui.registry.addMenuButton('acelletags', {
										text: '{{ trans('messages.editor.insert_tag') }}',
										fetch: function (callback) {
										var items = [];

										@foreach(Acelle\Model\Template::tags() as $tag)
											items.push({
												type: 'menuitem',
												text: '{{ "{".$tag["name"]."}" }}',
												onAction: function (_) {
													editor.insertContent('{{ "{".$tag["name"]."}" }}');
												}
											});
										@endforeach

										callback(items);
										}
									});

									editor.on('init', function(e) {
										$('.classic-loader').remove();
									});
								}
							});
						});
					</script> --}}
				</div>

				<div data-control="preview" style="display: none;">
					<div class="border p-3 shadow-sm">
						{{-- <div class="d-flex justify-content-center bg-light mb-3" style="padding: 30px 100px;">
							<svg style="width:100px" xmlns="http://www.w3.org/2000/svg" id="Layer_2" viewBox="0 0 177 177"><g id="Layer_1-2"><path d="M9.83,137.67c-2.79,0-5.12-.94-7.01-2.83-1.88-1.88-2.83-4.22-2.83-7.01V49.17c0-2.79.94-5.12,2.83-7.01,1.88-1.88,4.22-2.83,7.01-2.83h78.67c2.79,0,5.12.94,7.01,2.83,1.88,1.88,2.83,4.22,2.83,7.01v78.67c0,2.79-.94,5.12-2.83,7.01-1.88,1.88-4.22,2.83-7.01,2.83H9.83ZM19.67,118h59v-59H19.67v59ZM9.83,19.67c-2.79,0-5.12-.94-7.01-2.83-1.88-1.88-2.83-4.22-2.83-7.01S.94,4.71,2.83,2.83C4.71.94,7.05,0,9.83,0h157.33c2.79,0,5.12.94,7.01,2.83s2.83,4.22,2.83,7.01-.94,5.12-2.83,7.01-4.22,2.83-7.01,2.83H9.83ZM127.83,59c-2.79,0-5.12-.94-7.01-2.83s-2.83-4.22-2.83-7.01.94-5.12,2.83-7.01,4.22-2.83,7.01-2.83h39.33c2.79,0,5.12.94,7.01,2.83s2.83,4.22,2.83,7.01-.94,5.12-2.83,7.01-4.22,2.83-7.01,2.83h-39.33ZM127.83,98.33c-2.79,0-5.12-.94-7.01-2.83s-2.83-4.22-2.83-7.01.94-5.12,2.83-7.01c1.88-1.88,4.22-2.83,7.01-2.83h39.33c2.79,0,5.12.94,7.01,2.83,1.88,1.88,2.83,4.22,2.83,7.01s-.94,5.12-2.83,7.01-4.22,2.83-7.01,2.83h-39.33ZM127.83,137.67c-2.79,0-5.12-.94-7.01-2.83s-2.83-4.22-2.83-7.01.94-5.12,2.83-7.01,4.22-2.83,7.01-2.83h39.33c2.79,0,5.12.94,7.01,2.83s2.83,4.22,2.83,7.01-.94,5.12-2.83,7.01-4.22,2.83-7.01,2.83h-39.33ZM9.83,177c-2.79,0-5.12-.94-7.01-2.83-1.88-1.88-2.83-4.22-2.83-7.01s.94-5.12,2.83-7.01c1.88-1.88,4.22-2.83,7.01-2.83h157.33c2.79,0,5.12.94,7.01,2.83s2.83,4.22,2.83,7.01-.94,5.12-2.83,7.01c-1.88,1.88-4.22,2.83-7.01,2.83H9.83Z" style="fill:#e8eaed;"/></g></svg>
						</div> --}}
						<div data-control="preview-content">
							PREVIEW
						</div>
					</div>
				</div>

				@if ($errors->has('content'))
					<span class="help-block">
						{{ $errors->first('content') }}
					</span>
				@endif
			</div>
		</div>
			

		<div class="form-group">
			<label class="form-label mb-0">
				<span class="form-label d-flex align-items-top">
					<label class="d-block mb-0 me-3">
						<label class="checker ms-2">
							<input type="hidden" name="is_default" value="0" class="styled4">
							<input {{ $signature->is_default ? 'checked' : '' }} type="checkbox" name="is_default" value="1" class="styled4">
							<span class="checker-symbol"></span>
						</label>
					</label>
					<div>
						<span class="fw-semibold">{{ trans('messages.signature.make_default') }}</span>
						<p class="mb-0">{{ trans('messages.signature.make_default.desc') }}</p>
					</div>
				</span>
			</label>
		</div>
	</div>
</div>

<hr >
<div class="text-left">
	<button class="btn btn-secondary me-1"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
	<a href="{{ action('SignatureController@index') }}" class="btn btn-light me-2"><i class="icon-check"></i> {{ trans('messages.cancel') }}</a>
</div>


<script>
	$(() => {
		new SignatureEditor({
			container: $('#SignatureEditorContainer'),
		});
	});

	var SignatureEditor = class {
		constructor(options) {
			this.container = options.container;

			this.events();
		}

		getFormBox() {
			return this.container.find('[data-control="form"]');
		}

		getPreviewBox() {
			return this.container.find('[data-control="preview"]');
		}

		getShowFormButton() {
			return this.container.find('[data-control="show-form"]');
		}

		getShowPreviewButton() {
			return this.container.find('[data-control="show-preview"]');
		}

		events() {
			this.getShowPreviewButton().on('click', (e) => {
				e.preventDefault();

				this.preview();
			});

			this.getShowFormButton().on('click', (e) => {
				e.preventDefault();

				this.viewSourceCode();
			});
		}

		getContent() {
			return document.getElementById('signatureContent').value;
		}

		getPreviewContainer() {
			return this.container.find('[data-control="preview-content"]');
		}

		preview() {
			this.getFormBox().hide();
			this.getPreviewBox().show();

			this.getShowFormButton().removeClass('active');
			this.getShowPreviewButton().addClass('active');

			this.getPreviewContainer().html(this.getContent());
		}

		viewSourceCode() {
			this.getFormBox().show();
			this.getPreviewBox().hide();

			this.getShowFormButton().addClass('active');
			this.getShowPreviewButton().removeClass('active');
		}
	}
</script>
