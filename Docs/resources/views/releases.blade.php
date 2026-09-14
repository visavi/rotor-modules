@extends('layout')

@section('title', __('docs::rotor.page_releases'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/rotor">RotorCMS</a></li>
            <li class="breadcrumb-item active">{{ __('docs::rotor.page_releases') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($releases->isNotEmpty())
        <div class="rel-feed">
            @foreach ($releases as $i => $release)
                @include('docs::_release_card', [
                    'release' => $release,
                    'latest'  => $releases->currentPage() === 1 && $i === 0,
                ])
            @endforeach
        </div>

        <div class="mt-3">
            {{ $releases->links() }}
        </div>
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-circle-fill text-danger"></i>
            {{ __('docs::rotor.releases_error') }}
        </div>
    @endif
@stop

@include('docs::_release_styles')
