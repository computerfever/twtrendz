@extends('core::layouts.backend', ['menu' => 'templates',])
@section('title', trans('messages.templates'))

@section('page_header')
	<div class="page-title">
		<ul class="breadcrumb breadcrumb-caret position-right">
		  <li class="breadcrumb-item">
		  	<a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a>
		  </li>
		</ul>
		<div class="d-sm-flex align-items-center justify-content-between mt-3">
			<h1><span class="text-gear"><span class="material-symbols-rounded">format_list_bulleted</span> Templates</span></h1>
			<a href="{{ route('settings.templates.create') }}" class="btn btn-secondary">
				<span class="material-symbols-rounded">add</span> @lang('Create Template')
			</a>
		</div>
	</div>
@endsection

@section('content')
	<div class="row">
		<div class="col-md-12">
			@if($data->count() > 0)
				<div class="card">
					<div class="table-responsive">
						<table class="table card-table table-vcenter text-nowrap">
							<thead class="thead-dark">
								<tr>
									<th>@lang('Image')</th>
									<th>@lang('Category')</th>
									<th>@lang('Status')</th>
									<th>@lang('Action')</th>
								</tr>
							</thead>
							<tbody>
								@foreach($data as $item)
								<tr>
									<td><img src="{{ URL::to('/') }}/storage/thumb_templates/{{ $item->thumb }}" class="img-thumbnail" width="100" />
										<br>
										<small><a href="{{ route('settings.templates.edit', $item) }}">{{ $item->name }}</a></small>
										<br>
									</td>
									<td><small><a href="{{ route('settings.categories.edit', $item->category->id) }}">{{ $item->category->name }}</a></small></td>
									<td>
										@if($item->active)
											<small class="badge badge-success">@lang('Active')</small>
										@else
											<small class="badge badge-warning">@lang('Not active')</small>
										@endif
										<p class="mb-2"></p>
										@if($item->is_premium)
											<small class="badge badge-danger">@lang('Premium')</small>
										@else
											<small class="badge badge-success">@lang('Free')</small>
										@endif
										
									</td>
									<td>
										 <div class="d-flex">
											<div class="p-1">
												<a href="{{ route('settings.templates.edit', $item) }}" class="btn btn-primary">@lang('Edit')</a>
											</div>
											<div class="p-1 ">
												<a href="{{ route('settings.templates.builder', $item) }}">
												  <span class="btn btn-dark">Builder</span>
												</a>
											</div>
											<div class="p-1">
												<a target="_blank" href="{{ url('landingpages/preview-template/'.$item->id) }}" class="btn btn-secondary">@lang('Preview')</a>
											</div>
										</div>
										<div class="d-flex">
											<div class="p-1"> 
												<form method="post" action="{{ route('settings.templates.clone', $item) }}" >
													@csrf
													<button type="submit" class="btn btn-default border-0">
													@lang('Clone')
													</button>
												</form>
											</div>
											<div class="p-1 ">
												<form method="post" action="{{ route('settings.templates.destroy', $item) }}" onsubmit="return confirm('@lang('Confirm delete?')');">
													@csrf
													@method('DELETE')
													<button type="submit" class="btn btn-danger border-0">
														@lang('Delete')
													</button>
												</form>
											</div>
										</div>
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			@endif

			<div class="mt-4">
				{{ $data->appends( Request::all() )->links() }}
			</div>
			@if($data->count() == 0)
				<div class="alert alert-primary text-center">
					<i class="fe fe-alert-triangle mr-2"></i> @lang('No Templates found')
				</div>
			@endif
		</div>
	</div>
@endsection