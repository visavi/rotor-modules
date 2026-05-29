@extends('layout')

@section('title', __('classic::classic.recent_activity'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('classic::classic.recent_activity') }}</li>
        </ol>
    </nav>
@stop

@section('content')

@if(Route::has('forums.index'))
    <div class="section mb-3 shadow">
        <div class="section-title">
            <i class="fab fa-forumbee fa-lg text-muted"></i>
            {{ __('classic::classic.recent_topics') }}
        </div>
        {{ recentTopics() }}
    </div>
@endif

@if(Route::has('loads.index'))
    <div class="section mb-3 shadow">
        <div class="section-title">
            <i class="fa fa-download fa-lg text-muted"></i>
            {{ __('classic::classic.recent_files') }}
        </div>
        {{ recentDowns() }}
    </div>
@endif

@if(Route::has('blogs.index'))
    <div class="section mb-3 shadow">
        <div class="section-title">
            <i class="fa fa-globe fa-lg text-muted"></i>
            {{ __('classic::classic.recent_articles') }}
        </div>
        {{ recentArticles() }}
    </div>
@endif

@if(Route::has('photos.index'))
    <div class="section mb-3 shadow">
        <div class="section-title">
            <i class="fa fa-image fa-lg text-muted"></i>
            {{ __('classic::classic.recent_photos') }}
        </div>
        {{ recentPhotos() }}
    </div>
@endif

@stop
