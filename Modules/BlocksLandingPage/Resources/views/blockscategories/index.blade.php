@extends('core::layouts.backend', ['menu' => 'blockscategories'])
@section('title', trans('Block Categories'))

@section('page_header')
	<div class="page-title">
		<ul class="breadcrumb breadcrumb-caret position-right">
		  <li class="breadcrumb-item">
		  	<a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a>
		  </li>
		</ul>
		<div class="d-sm-flex align-items-center justify-content-between mt-3">
			<h1><span class="text-gear"><span class="material-symbols-rounded">view_list</span> @lang('Block Categories')</span></h1>
			<a href="{{ route('settings.block-categories.create') }}" class="btn btn-secondary">
				<span class="material-symbols-rounded">add</span> @lang('Create')
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
									<th>@lang('Name')</th>
									<th>@lang('Date Created')</th>
									<th>@lang('Date Modified')</th>
									<th>@lang('Action')</th>
								</tr>
							</thead>
							<tbody>
								@foreach($data as $item)
								<tr>
									<td><a href="{{ route('settings.block-categories.edit', $item) }}">{{ $item->name }}</a></td>
								  <td><div class="small text-muted">{{$item->created_at->format('M j, Y')}}</div></td>
									<td><div class="small text-muted">{{$item->updated_at->format('M j, Y')}}</div></td>
									<td>
										<div class="d-flex">
											<div class="p-1 ">
												<a href="{{ route('settings.block-categories.edit', $item) }}" class="btn btn-primary">@lang('Edit')</a>
											</div>
											<div class="p-1 ">
												<form method="post" action="{{ route('settings.block-categories.destroy', $item) }}" onsubmit="return confirm('@lang('Confirm delete?')');">
													@csrf
													@method('DELETE')
													<button type="submit" class="btn btn-danger btn-clean">
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
					<!-- Pagination -->
					<div class="mt-2 mb-4">
						{{ $data->appends( Request::all() )->links() }}
					</div>
				</div>
			@endif


			@if($data->count() == 0)
			<div class="alert alert-primary text-center">
				<i class="fe fe-alert-triangle mr-2"></i> @lang('No block categories found')
			</div>
			@endif
		</div>
	</div>
@endsection