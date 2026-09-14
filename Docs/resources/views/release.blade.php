@extends('layout')

@section('title', __('docs::rotor.page_release', ['version' => $release['tag_name']]))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/rotor">RotorCMS</a></li>
            <li class="breadcrumb-item"><a href="/rotor/releases">{{ __('docs::rotor.page_releases') }}</a></li>
            <li class="breadcrumb-item active">{{ $release['tag_name'] }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @include('docs::_release_card', [
        'release' => $release,
        'latest'  => $latest,
        'single'  => true,
    ])
@stop

@include('docs::_release_styles')
