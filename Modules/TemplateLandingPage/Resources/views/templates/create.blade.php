@extends('core::layouts.backend', ['menu' => 'templates',])
@section('title', trans('Create new template'))

@section('page_header')
	<div class="page-title">				
		<ul class="breadcrumb breadcrumb-caret position-right">
		  <li class="breadcrumb-item">
		  	<a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a>
		  </li>
		</ul>
		<h1><span class="text-gear"><span class="material-symbols-rounded">view_list</span> New Template</span></h1>
	</div>
@endsection

@section('content')

<div class="row">
	<div class="col-md-12">
		 <form role="form" method="post" action="{{ route('settings.templates.store') }}" enctype="multipart/form-data">
			@csrf
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-12">

							<div class="form-group">
								<label class="form-label">@lang('Name')</label>
								<input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="@lang('Name')">
							</div>
							<div class="form-group">
								<label class="form-label">@lang('Categories')</label>
								 <select name="category_id" class="select required" required>
									@foreach ($categories as $item)
										<option value="{{$item->id}}">{{$item->name}}</option>
									@endforeach
								</select>
							</div>
							<div class="form-group">
								<label class="form-label">@lang('Thumb') (400x250) or (1200x750)</label>
								<input name="thumb" type="file"><br>
							</div>
							<div class="form-group">
								<p><strong>@lang('Variable available in Main page content style, Thank You Page Content Style'):</strong>  <code>##image_url##</code></p>
								<label class="form-label">@lang('Main page content')</label>
								<textarea rows="6" name="content" class="form-control"></textarea>
							</div>
							<div class="form-group">
								<label class="form-label">@lang('Style Main page')</label>
								<textarea rows="6" name="style" class="form-control"></textarea>
							</div>
							<div class="form-group">
								<label class="form-label">@lang('Thank You Page content')</label>
								<textarea rows="6" name="thank_you_page" class="form-control"></textarea>
							</div>
							<div class="form-group">
								<label class="form-label">@lang('Thank You Page Style')</label>
								<textarea rows="6" name="thank_you_style" class="form-control"></textarea>
							</div>
							<div class="form-group">
								<div class="form-label">@lang('Active')</div>
								<label class="custom-switch">
									<input type="checkbox" name="active" class="custom-switch-input">
									<span class="custom-switch-indicator"></span>
									<span class="custom-switch-description">@lang('Allow active template')</span>
								</label>
							</div>
							<div class="form-group">
								<div class="form-label">@lang('Premium')</div>
								<label class="custom-switch">
									<input type="checkbox" name="is_premium" class="custom-switch-input">
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
						<button class="btn btn-success ml-auto">@lang('Save')</button>
					</div>
				</div>
			</div>
		</form>


	</div>
	
</div>


@stop