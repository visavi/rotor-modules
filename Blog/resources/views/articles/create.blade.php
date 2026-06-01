@extends('layout')

@section('title', __('blog::blogs.title_create'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('blogs.index') }}">{{ __('blog::blogs.blogs') }}</a></li>
            <li class="breadcrumb-item active">{{ __('blog::blogs.title_create') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section-form mb-3 shadow">
        @include('blog::articles/_form')
    </div>


@stop
