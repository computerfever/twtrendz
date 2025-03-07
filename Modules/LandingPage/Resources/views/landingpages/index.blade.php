@extends('core::layouts.frontend', ['menu' => 'landingpages'])
@section('title', trans('My Landing Pages'))


@section('page_header')
  <div class="page-title">        
    <ul class="breadcrumb breadcrumb-caret position-right">
      <li class="breadcrumb-item">
        <a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a>
      </li>
    </ul>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
      <h1>
        <span class="text-gear"><span class="material-symbols-rounded">format_list_bulleted</span> @lang('My Landing Pages')</span>
      </h1>
      <form method="get" action="{{ route('landingpages.index') }}" class="my-3 my-lg-0 navbar-search">
        <div class="input-group">
          <input type="text" name="search" value="{{ Request::get('search') }}" class="form-control bg-light border-0 small" placeholder="@lang('Search landing pages')" aria-label="Search" aria-describedby="basic-addon2">
          <div class="input-group-append">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search fa-sm"></i></button>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('content')
  <div class="row">
    <div class="col-sm-12">

      @if($data->count() > 0)
      <div class="card">
        <div class="table-responsive min-h-200">
          <table class="table table-bordered table-striped">
            <thead class="table-dark">
              <tr>
                <th>@lang('Name')</th>
                <th>@lang('Type')</th>
                <th>@lang('Publish')</th>
                <th>@lang('Domain')</th>
                <th>@lang('Settings')</th>
                <th>@lang('Action')</th>
              </tr>
            </thead>
            <tbody class="table-group-divider">
              @foreach($data as $item)
              <tr>
                <td>
                  <a href="{{ route('landingpages.builder', $item->code) }}">{{ $item->name }}</a>
                </td>
                <td>
                  @if(isset($item->template->name))
                    {{$item->template->name}}
                  @else
                    @lang('None')
                  @endif
                </td>
                <td>
                  @if($item->is_publish)
                  <span class="badge badge-success">@lang('Published')</span>
                  @else
                  <span class="badge badge-danger">@lang('Not publish')</span>
                  @endif
                  
                </td>
                <td>
                  @if($item->domain_type == 0)
                    @if($item->admin ==1)
                    <a href="https://{{$customerLandingPageUrl}}" target="_blank">{{$customerLandingPageUrl}}</a>
                    @else
                    <a href="https://{{$item->sub_domain}}" target="_blank">{{$item->sub_domain}}</a>
                    @endif
                  @elseif($item->domain_type == 1)
                  <a href="https://{{$item->custom_domain}}">{{$item->custom_domain}}</a>
                  @endif
                </td>
                <td align="center">
                  @if($item->user_id == Auth::user()->customer->id)
                    <a href="{{route('landingpages.setting', $item->code)}}" class="btn btn-primary"><i class="fas fa-cog"></i> @lang('Setting')</a>
                  @else
                    Not Allowed To Change.
                  @endif
                </td>
                <td align="center">
                  @if($item->user_id == Auth::user()->customer->id)
                  <div class="dropdown no-arrow">
                    <a class="btn btn-primary" href="#" role="button" data-toggle="dropdown">
                      <i class="fas fa-ellipsis-v fa-sm fa-fw"></i> Actions
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" style="">
                      <a href="{{ route('landingpages.builder', $item->code) }}" class="dropdown-item">Builder</a>
                      @if($item->admin == 0)
                      <form method="post" action="{{ route('landingpages.clone', $item) }}" >
                        @csrf
                        <button type="submit" class="dropdown-item">@lang('Clone')</button>
                      </form>
                      @endif
                      <form method="post" action="{{ route('landingpages.delete', $item->code) }}" onsubmit="return confirm('@lang('Confirm delete?')');">
                        @csrf
                        <button class="dropdown-item">@lang('Delete')</button>
                      </form>
                    </div>
                  </div>
                  @else
                    Not Allowed To Change.
                  @endif
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
    </div>
  </div>
  <div class="row">
    <div class="col-lg-12">
      @if($data->count() == 0)
      <div class="text-center">
        <div class="error mx-auto mb-3"><i class="far fa-file-alt fa-2xl"></i></div>
        <p class="lead text-gray-800">@lang('No Landing Page Found')</p>
        <p class="text-gray-500">@lang("You don't have any Landing Page").</p>
        <a href="{{ route('alltemplates') }}" class="btn btn-primary">
          <span class="text">@lang('New Landing Page')</span>
        </a>
      </div>
      @endif
    </div>
  </div>
@endsection