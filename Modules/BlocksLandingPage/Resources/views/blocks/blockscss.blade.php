@extends('core::layouts.backend', ['menu' => 'blocks'])
@section('title', trans('Update Blocks Css'))

@section('page_header')
	<div class="page-title">				
		<ul class="breadcrumb breadcrumb-caret position-right">
		  <li class="breadcrumb-item">
		  	<a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a>
		  </li>
		</ul>
		<h1><span class="text-gear"><span class="material-symbols-rounded">view_list</span> @lang('Update style blocks css')</span></h1>
	</div>
@endsection

@section('content')
	<div class="row">
		<div class="col-md-12">
			 <form role="form" method="post" action="{{ route('settings.blocks.updateblockscss') }}">
				@csrf
				<div class="card">
					<div class="card-body">
						<div class="row">
							<div class="col-md-12">
								<p><small>@lang('All blocks css styles are here. You can add or edit it when adding or editing a block').</small></p>
								<div class="form-group">
									<p><strong>@lang('Variable available in Style content'):</strong>  <code>##image_url##</code></p>
									<label class="form-label">@lang('Blocks css')</label>
									<textarea rows="22" name="blockscss" class="form-control">{{ $blockscss }}</textarea>
								</div>
							</div>
							
						</div>
					</div>
					<div class="card-footer">
						<div class="d-flex">
							<a href="{{ route('settings.blocks.index') }}" class="btn btn-secondary">@lang('Cancel')</a>
							<button class="btn btn-success ml-auto">@lang('Save')</button>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
@endsection