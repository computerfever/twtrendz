@extends('core::layouts.backend', ['menu' => 'categories',])
@section('title', trans('Update Category'))

@section('page_header')
	<div class="page-title">
		<ul class="breadcrumb breadcrumb-caret position-right">
		  <li class="breadcrumb-item">
		  	<a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a>
		  </li>
		</ul>
		<h1><span class="text-gear"><span class="material-symbols-rounded">view_list</span>@lang('Update Category')</span></h1>
	</div>
@endsection

@section('content')
	<div class="row">
		<div class="col-md-12">
			<form role="form" method="post" action="{{ route('settings.categories.update', $category->id) }}" enctype="multipart/form-data">
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
								<div class="form-group">
									<label class="form-label">@lang('Group Categories')</label>
									 <select name="group_category_id" class="select select-search">
										@foreach ($groupCategories as $item)
											<option value="{{$item->id}}"
											 @if ($item->id == $category->group_category_id)
												selected="selected"
											@endif>
											{{$item->name}}
											</option>
										@endforeach
									</select>
								</div>
							</div>
							
						</div>

					</div>
					<div class="card-footer">
						<div class="d-flex">
							<a href="{{ route('settings.categories.index') }}" class="btn btn-secondary">@lang('Cancel')</a>
							<button class="btn btn-primary ml-auto">@lang('Save')</button>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
@endsection