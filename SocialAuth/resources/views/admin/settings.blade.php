@extends('layout')

@section('title', __('social_auth::social_auth.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'SocialAuth']) }}">{{ __('social_auth::social_auth.module_name') }}</a></li>
            <li class="breadcrumb-item active">{{ __('social_auth::social_auth.settings') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('social_auth::social_auth.settings') }}</h1>
@stop

@section('content')
<form method="post" action="{{ route('social_auth.settings.update') }}">
    @csrf

    <div class="card mb-4">
        <div class="card-body">
            <div class="form-check">
                <input type="hidden" value="0" name="sets[social_autolink_email]">
                <input type="checkbox" class="form-check-input" value="1"
                       name="sets[social_autolink_email]"
                       id="social_autolink_email"
                    {{ ! empty($settings['social_autolink_email']) ? ' checked' : '' }}>
                <label class="form-check-label" for="social_autolink_email">
                    {{ __('social_auth::social_auth.autolink_email') }}
                </label>
                <div class="form-text">{{ __('social_auth::social_auth.autolink_email_hint') }}</div>
            </div>
        </div>
    </div>

    @foreach(['google', 'github', 'yandex', 'vk'] as $provider)
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <strong>{{ ucfirst($provider) }}</strong>
        </div>
        <div class="card-body">
            <div class="form-check mb-3">
                <input type="hidden" value="0" name="sets[social_{{ $provider }}_enabled]">
                <input type="checkbox" class="form-check-input" value="1"
                       name="sets[social_{{ $provider }}_enabled]"
                       id="{{ $provider }}_enabled"
                    {{ ! empty($settings['social_' . $provider . '_enabled']) ? ' checked' : '' }}>
                <label class="form-check-label" for="{{ $provider }}_enabled">
                    {{ __('social_auth::social_auth.enable_provider') }}
                </label>
            </div>

            <div class="mb-3">
                <label for="{{ $provider }}_client_id" class="form-label">Client ID:</label>
                <input type="text" class="form-control" id="{{ $provider }}_client_id"
                       name="sets[social_{{ $provider }}_client_id]"
                       value="{{ $settings['social_' . $provider . '_client_id'] ?? '' }}"
                       autocomplete="off">
            </div>

            <div class="mb-3">
                <label for="{{ $provider }}_client_secret" class="form-label">Client Secret:</label>
                <input type="text" class="form-control" id="{{ $provider }}_client_secret"
                       name="sets[social_{{ $provider }}_client_secret]"
                       value="{{ $settings['social_' . $provider . '_client_secret'] ?? '' }}"
                       autocomplete="off">
            </div>

            <div class="text-muted small">
                {{ __('social_auth::social_auth.callback_url') }}:
                <code>{{ route('social.callback', ['provider' => $provider]) }}</code>
            </div>
        </div>
    </div>
    @endforeach

    <button class="btn btn-primary">{{ __('main.save') }}</button>
</form>
@stop
