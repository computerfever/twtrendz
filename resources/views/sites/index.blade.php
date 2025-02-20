@extends('layouts.core.frontend', [
    'menu' => 'site',
])

@section('title', trans('messages.dashboard'))

@section('page_header')
    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
        </ul>
        <h1>
            <span class="text-semibold"><span class="material-symbols-rounded">web</span> {{ trans('messages.site.sites') }}</span>
        </h1>
    </div>
@endsection

@section('content')
    <p>{!! trans('messages.site.intro') !!}</p>

    <ul class="nav nav-tabs nav-tabs-top nav-underline mb-1">
        <li class="nav-item active">
            <a href="http://acelle.com/lists/67555aae0d3de/overview" class="nav-link">
                <span class="d-flex align-items-center">
                    <span class="me-2">Landing pages</span>
                    <span class="badge bg-secondary">0</span>
                </span>
            </a>
        </li>
        <li class="nav-item">
            <a href="http://acelle.com/lists/67555aae0d3de/overview" class="nav-link">
                <span class="d-flex align-items-center">
                    <span class="me-2">Websites</span>
                    <span class="badge bg-secondary">0</span>
                </span>
            </a>
        </li>
    </ul>

    <div class="mt-5">
        <div class="d-flex w-100">
            <div class="d-flex align-items-center px-5 py-2 rounded-3 bg-light">
                <span class="d-block mb-4 text-muted2">
                    <svg style="fill:currentColor;width:100px;height:100px;" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed"><path d="m509.77-482 61-36.85 61 36.85q5.23 2.46 9.58-.38 4.34-2.85 2.34-8.31L627-560.15l53.54-46.31q4.23-3.46 2.73-8.19-1.5-4.73-6.96-5.73l-70.46-6.24-27.62-65.23q-2-5.23-7.46-5.23t-7.46 5.23l-27.62 65.23-70.46 6.24q-5.46 1-6.96 5.73t2.73 8.19l53.54 46.31-16.69 69.46q-2 5.46 2.34 8.31 4.35 2.84 9.58.38ZM366.15-301.54q-27.61 0-46.11-18.5t-18.5-46.11v-409.23q0-27.62 18.5-46.12 18.5-18.5 46.11-18.5h409.23q27.62 0 46.12 18.5Q840-803 840-775.38v409.23q0 27.61-18.5 46.11t-46.12 18.5H366.15Zm0-40h409.23q10.77 0 17.7-6.92 6.92-6.92 6.92-17.69v-409.23q0-10.77-6.92-17.7-6.93-6.92-17.7-6.92H366.15q-10.77 0-17.69 6.92-6.92 6.93-6.92 17.7v409.23q0 10.77 6.92 17.69 6.92 6.92 17.69 6.92Zm204.62-229.23ZM218-164Zm29.23 36.69q-26.85 3.23-47.96-12.92-21.12-16.15-24.35-43l-49.15-388.54q-3.23-26.85 13.54-48.61 16.77-21.77 43.61-24.47l17.39-.77q8.54-.69 15.11 4.77 6.58 5.47 6.58 15 0 7.31-5.12 13.31-5.11 6-12.42 6.69l-15.61.77q-10.77.77-16.93 8.85-6.15 8.08-4.61 18.85l47.84 388.76q1.54 10.77 9.23 16.93 7.7 6.15 18.47 4.61l470.92-58.77q8.54-1.23 15 3.77 6.46 5 7.69 13.54 1.23 8.54-3.77 14.5-5 5.96-13.54 7.19l-471.92 59.54Z"/></svg>
                </span>
            </div>
            <div class="px-5 py-5">
                <h2>Create your first landing page</h2>
                <ul class="ms-0 mb-0">
                    <li class="mb-1">
                        Signup forms to gather email addresses
                    </li>
                    <li class="mb-1">
                        Content blocks to build interest
                    </li>
                    <li class="mb-1">
                        Newsletter archives to promote content
                    </li>
                    <li class="mb-1">
                        Contact forms to collect information
                    </li>
                    <li class="mb-1">
                        Surveys and other blocks to drive engagement
                    </li>
                </ul>

                <div class="mt-4">
                    <a href="{{ action('SiteController@create') }}" class="btn btn-primary px-4 py-2">Create</a>
                    {{-- <a href="" class="btn btn-link">Learn more</a> --}}
                </div>
            </div>
        </div>
    </div>
    
@endsection
