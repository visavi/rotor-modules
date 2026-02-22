@extends('layout')

@section('title', __('user_location::locations.title'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('user_location::locations.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($locations->isNotEmpty())
        @foreach ($locations as $location)
        <div class="section mb-3 shadow">
            <div class="section-content">
                <i class="fa-solid fa-globe"></i> <a class="fw-bold" href="{{ $location->path }}">{{ $location->title }}</a> - {{ $location->path }}
            </div>
            <div class="section-body">
                <span class="avatar-micro">{{ $location->user->getAvatarImage() }}</span> {{ $location->user->getProfile() }}
                <small class="section-date text-muted fst-italic">{{ dateFixed($location->created_at) }}</small>
            </div>
        </div>

        @endforeach
    @else
        {{ showError(__('user_location::locations.empty_locations')) }}
    @endif

    {{ $locations->links() }}
@stop
