@extends('admin/settings/layout')

@section('title', __('gift::gifts.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'gift']) }}">{{ __('admin.modules.module') }} {{ __('gift::gifts.title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('gift::gifts.settings') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('gift::gifts.settings') }}</h1>
@stop

@section('settings')
<form method="post" action="{{ route('gift.settings.update') }}">
    @csrf
    <div class="mb-3{{ hasError('sets[gift_per_page]') }}">
        <label for="gift_per_page" class="form-label">{{ __('gift::gifts.setting_per_page') }}:</label>
        <input type="number" class="form-control" id="gift_per_page" name="sets[gift_per_page]" min="1" max="100" value="{{ old('sets.gift_per_page', $settings['gift_per_page']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[gift_per_page]') }}</div>
    </div>

    <div class="mb-3{{ hasError('sets[gift_days]') }}">
        <label for="gift_days" class="form-label">{{ __('gift::gifts.setting_days') }}:</label>
        <input type="number" class="form-control" id="gift_days" name="sets[gift_days]" min="1" max="3650" value="{{ old('sets.gift_days', $settings['gift_days']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[gift_days]') }}</div>
    </div>

    <div class="mb-3{{ hasError('sets[gift_max_users]') }}">
        <label for="gift_max_users" class="form-label">{{ __('gift::gifts.setting_max_users') }}:</label>
        <input type="number" class="form-control" id="gift_max_users" name="sets[gift_max_users]" min="1" max="100" value="{{ old('sets.gift_max_users', $settings['gift_max_users']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[gift_max_users]') }}</div>
    </div>

    <button class="btn btn-primary">{{ __('main.save') }}</button>
</form>
@stop
