@extends('admin/settings/layout')

@section('title', __('antimat::antimat.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item active">{{ __('antimat::antimat.settings') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('antimat::antimat.settings') }}</h1>
@stop

@section('settings')
    <form method="post" action="{{ route('antimat.settings.update') }}">
        @csrf
        <div class="mb-3{{ hasError('sets[antimat_replace]') }}">
            <label for="antimat_replace" class="form-label">{{ __('antimat::antimat.settings_replace') }}:</label>
            <input type="text" class="form-control" id="antimat_replace" name="sets[antimat_replace]" maxlength="20" value="{{ old('sets.antimat_replace', $settings['antimat_replace'] ?? '***') }}" required>
            <div class="invalid-feedback">{{ textError('sets[antimat_replace]') }}</div>
            <div class="form-text">{{ __('antimat::antimat.settings_replace_hint') }}</div>
        </div>

        <div class="form-check form-switch mb-3">
            <input type="hidden" value="0" name="sets[antimat_whole_word]">
            <input type="checkbox" role="switch" class="form-check-input" value="1" name="sets[antimat_whole_word]" id="antimat_whole_word"{{ old('sets.antimat_whole_word', $settings['antimat_whole_word'] ?? 0) ? ' checked' : '' }}>
            <label class="form-check-label" for="antimat_whole_word">{{ __('antimat::antimat.settings_whole_word') }}</label>
            <div class="form-text">{{ __('antimat::antimat.settings_whole_word_hint') }}</div>
        </div>

        <button class="btn btn-primary">{{ __('main.save') }}</button>
    </form>
@stop
