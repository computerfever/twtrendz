@extends('core::layouts.backend', ['menu' => 'groupcategories',])
@section('title', trans('Update Group catetory'))

@section('page_header')
	<div class="page-title">
		<ul class="breadcrumb breadcrumb-caret position-right">
		  <li class="breadcrumb-item">
		  	<a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a>
		  </li>
		</ul>
		<h1><span class="text-gear"><span class="material-symbols-rounded">view_list</span> @lang('Update Group category')</span>
		</h1>
	</div>
@endsection

@section('content')
	<div class="row">
		<div class="col-md-12">

			<form role="form" method="post" action="{{ route('settings.groupcategories.update', $category->id) }}" enctype="multipart/form-data">
				@csrf
				@method('PUT')

				<div class="card">
					<div class="card-body">
						<div class="row">
							<div class="col-md-12">

								<div class="form-group">
									<label class="form-label">@lang('Name')</label>
									<input type="text" name="name" value="{{$category->name}}" class="form-control" placeholder="@lang('Name')">
								</div>
								

							</div>
							
						</div>

					</div>
					<div class="card-footer">
						<div class="d-flex">
							<a href="{{ route('settings.groupcategories.index') }}" class="btn btn-secondary">@lang('Cancel')</a>
							<button class="btn btn-primary ml-auto">@lang('Save')</button>
						</div>
					</div>
				</div>
			</form>

		</div>
	</div>
@stop