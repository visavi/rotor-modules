@extends('layout')

@section('title', __('load::loads.publish_down'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('loads.index') }}">{{ __('load::loads.loads') }}</a></li>
            <li class="breadcrumb-item active">{{ __('load::loads.publish_down') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section-form mb-3 shadow">
        @include('load::downs/_form')
    </div>
@stop
