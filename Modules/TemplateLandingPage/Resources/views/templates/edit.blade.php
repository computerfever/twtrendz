@extends('core::layouts.backend', ['menu' => 'templates',])
@section('title', trans('Update Template'))

@section('page_header')
	<div class="page-title">				
		<ul class="breadcrumb breadcrumb-caret position-right">
		  <li class="breadcrumb-item">
		  	<a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a>
		  </li>
		</ul>
		<h1><span class="text-gear"><span class="material-symbols-rounded">draw</span> Update Template</span></h1>
	</div>
@endsection

@section('content')
<div class="row">
	<div class="col-md-12">
		<form role="form" method="post" action="{{ route('settings.templates.update', $template->id) }}" enctype="multipart/form-data">
			@csrf
			@method('PUT')

			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label class="form-label">@lang('Name')</label>
								<input type="text" name="name" value="{{$template->name}}" class="form-control" placeholder="@lang('Name')">
							</div>
							<div class="form-group">
								<label class="form-label">@lang('Categories')</label>
								 <select name="category_id" class="select required">
									@foreach ($categories as $item)
										<option value="{{$item->id}}"
										 @if ($item->id == $template->category_id)
											selected="selected"
										@endif>
										{{$item->name}}
										</option>
									@endforeach
								</select>
							</div>
							<div class="form-group">
								<label class="form-label">@lang('Thumb') (400x250) or (1200x750)</label>
								 <input name="thumb" type="file"><br>
								 <img src="{{ URL::to('/') }}/storage/thumb_templates/{{ $template->thumb }}" data-value="" class="img-thumbnail" style="max-width: 100px; max-height: 100px;" />
								<input type="hidden" name="hidden_image" value="{{ $template->thumb }}" />
							</div>
							<div class="form-group">
								<p><strong>@lang('Variable available in Main page content style, Thank You Page Content Style'):</strong>  <code>##image_url##</code></p>
								<label class="form-label">@lang('Main page content')</label>
								<textarea rows="6" name="content" class="form-control">{{$template->content}}</textarea>
							</div>
							<div class="form-group">
								<label class="form-label">@lang('Style Main page')</label>
								<textarea rows="6" name="style" class="form-control">{{$template->style}}</textarea>
							</div>
							<div class="form-group">
								<label class="form-label">@lang('Thank You Page content')</label>
								<textarea rows="6" name="thank_you_page" class="form-control">{{$template->thank_you_page}}</textarea>
							</div>
							<div class="form-group">
								<label class="form-label">@lang('Thank You Page Style')</label>
								<textarea rows="6" name="thank_you_style" class="form-control">{{$template->thank_you_style}}</textarea>
							</div>

							<div class="form-group">
								<div class="form-label">@lang('Active')</div>
								<label class="custom-switch">
									@if ($template->active)
										<input type="checkbox" name="active" value="1" class="custom-switch-input" checked>
									@else 
										<input type="checkbox" name="active" value="1" class="custom-switch-input" >
									@endif
									
									<span class="custom-switch-indicator"></span>
									<span class="custom-switch-description">@lang('Active template')</span>
								</label>
							</div>
							<div class="form-group">
								<div class="form-label">@lang('Premium')</div>
								<label class="custom-switch">
									@if ($template->is_premium)
										<input type="checkbox" name="is_premium"  value="1" class="custom-switch-input" checked>
									@else 
										<input type="checkbox" name="is_premium" class="custom-switch-input" >
									@endif
								<span class="custom-switch-indicator"></span>
									<span class="custom-switch-description">@lang('Premium template')</span>
								</label>
							</div>

						</div>
						
					</div>

				</div>
				<div class="card-footer">
					<div class="d-flex">
						<a href="{{ route('settings.templates.index') }}" class="btn btn-secondary">@lang('Cancel')</a>
						<button class="btn btn-primary ml-auto">@lang('Save')</button>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
@endsection