@extends('themes::elsam.layout')
@section('content')
  @include('themes::elsam.nav')
  
  <section class="pt-4 pb-4">
      <div class="home-center">
          <div class="home-desc-center">
              <div class="container">
                  <div class="row mt-3">
                      <div class="col-md-12">
                          <div class="row mb-2">
                              <div class="col-md-12">
                                  <h3><strong>@lang('All blog')</strong></h3>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </section>
  <section class="">
      <div class="container">
          <div class="row mb-4">
              <div class="col-md-12">
                  <div class="row mt-4 mb-4">
                      @foreach($data as $item)
                      <div class="col-md-4 col-xs-12 col-sm-6">
                          @include('themes::elsam.includes.item_blog', ['blog' => $item])
                      </div>
                      @endforeach
                  </div>
                  <div class="row my-5">
                      <div class="col-auto">
                          {{ $data->appends( Request::all() )->links() }}
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </section>
@stop