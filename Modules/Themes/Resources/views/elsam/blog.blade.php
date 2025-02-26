@extends('themes::elsam.layout')
@section('content')
@include('themes::elsam.nav')

<section class="section-sm">
    <div class="container">

        <div class="row">
            <div class="col-lg-12">
                <div class="title-box text-center">
                    <h3 class="title-heading mt-4">{{$blog->title}}</h3>
                    <div class="employer-metas mt-3">
                        <div class="employer-location">
                            <a href="{{ route('blogs', ['category_id' => $blog->category->id]) }}">{{ $blog->category->name }}</a>
                        </div>
                        <div class="employer-location">
                            {{ \Carbon\Carbon::parse($blog->created_at)->toFormattedDateString() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-5 pt-4">
            {!! $blog->content !!}
        </div>
    </div>
</section>

@stop
