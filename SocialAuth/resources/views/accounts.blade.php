@extends('layout')

@section('title', __('social_auth::social_auth.linked_accounts'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('users.user', ['login' => $user->login]) }}">{{ $user->getName() }}</a></li>
            <li class="breadcrumb-item active">{{ __('social_auth::social_auth.linked_accounts') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('social_auth::social_auth.linked_accounts') }}</h1>
@stop

@section('content')
@if(empty($availableProviders))
    <div class="alert alert-warning">{{ __('social_auth::social_auth.no_providers_enabled') }}</div>
@else
    <div class="list-group">
        @foreach($availableProviders as $provider)
            <div class="list-group-item d-flex justify-content-between align-items-center">
                @php $cfg = \Modules\SocialAuth\Models\Social::providerConfig($provider) @endphp
                <span>
                    <i class="{{ $cfg['icon'] }}" style="color: {{ $cfg['color'] }}"></i>
                    {{ $cfg['name'] }}
                </span>
                @if(isset($socials[$provider]))
                    <form method="post" action="{{ route('social.unlink', ['provider' => $provider]) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">
                            {{ __('social_auth::social_auth.unlink') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('social.link', ['provider' => $provider]) }}" class="btn btn-sm btn-outline-primary">
                        {{ __('social_auth::social_auth.link') }}
                    </a>
                @endif
            </div>
        @endforeach
    </div>
@endif
@stop
