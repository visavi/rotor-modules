@extends('layout')

@section('title', __('share::share.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'Share']) }}">{{ __('admin.modules.module') }} {{ __('share::share.share') }}</a></li>
            <li class="breadcrumb-item active">{{ __('share::share.settings') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('share::share.settings') }}</h1>
@stop

@section('content')
    <form method="post" action="{{ route('share.settings.update') }}">
        @csrf

        <p class="text-muted">{{ __('share::share.settings_hint') }}</p>

        @foreach (config('share.networks') as $key => $network)
            <div class="form-check form-switch mb-2">
                <input type="hidden" value="0" name="sets[share_{{ $key }}]">
                <input type="checkbox" class="form-check-input" value="1" name="sets[share_{{ $key }}]" id="share_{{ $key }}"{{ old('sets.share_' . $key, $settings['share_' . $key] ?? 0) ? ' checked' : '' }}>
                <label class="form-check-label" for="share_{{ $key }}">
                    <i class="{{ $network['icon'] }} fa-fw" style="color: {{ $network['color'] }}"></i> {{ $network['name'] }}
                </label>
            </div>
        @endforeach

        <div class="form-check form-switch mb-3">
            <input type="hidden" value="0" name="sets[share_copy]">
            <input type="checkbox" class="form-check-input" value="1" name="sets[share_copy]" id="share_copy"{{ old('sets.share_copy', $settings['share_copy'] ?? 0) ? ' checked' : '' }}>
            <label class="form-check-label" for="share_copy">
                <i class="fas fa-link fa-fw"></i> {{ __('main.copy_link') }}
            </label>
        </div>

        <button class="btn btn-primary">{{ __('main.save') }}</button>
    </form>
@stop
