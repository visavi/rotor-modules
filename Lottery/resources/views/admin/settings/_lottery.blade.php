@extends('admin/settings/layout')

@section('title', __('lottery::lottery.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'Lottery']) }}">{{ __('admin.modules.module') }} {{ __('lottery::lottery.title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('lottery::lottery.settings') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('lottery::lottery.settings') }}</h1>
@stop

@section('settings')
<form method="post" action="{{ route('lottery.settings.update') }}">
    @csrf
    <div class="mb-3{{ hasError('sets[lottery_jackpot]') }}">
        <label for="lottery_jackpot" class="form-label">{{ __('lottery::lottery.setting_jackpot') }}:</label>
        <input type="number" class="form-control" id="lottery_jackpot" name="sets[lottery_jackpot]" min="0" value="{{ old('sets.lottery_jackpot', $settings['lottery_jackpot']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[lottery_jackpot]') }}</div>
    </div>

    <div class="mb-3{{ hasError('sets[lottery_ticket_price]') }}">
        <label for="lottery_ticket_price" class="form-label">{{ __('lottery::lottery.setting_ticket_price') }}:</label>
        <input type="number" class="form-control" id="lottery_ticket_price" name="sets[lottery_ticket_price]" min="0" value="{{ old('sets.lottery_ticket_price', $settings['lottery_ticket_price']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[lottery_ticket_price]') }}</div>
    </div>

    <div class="mb-3{{ hasError('sets[lottery_min]') }}">
        <label for="lottery_min" class="form-label">{{ __('lottery::lottery.setting_min') }}:</label>
        <input type="number" class="form-control" id="lottery_min" name="sets[lottery_min]" min="0" max="32767" value="{{ old('sets.lottery_min', $settings['lottery_min']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[lottery_min]') }}</div>
    </div>

    <div class="mb-3{{ hasError('sets[lottery_max]') }}">
        <label for="lottery_max" class="form-label">{{ __('lottery::lottery.setting_max') }}:</label>
        <input type="number" class="form-control" id="lottery_max" name="sets[lottery_max]" min="0" max="32767" value="{{ old('sets.lottery_max', $settings['lottery_max']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[lottery_max]') }}</div>
    </div>

    <button class="btn btn-primary">{{ __('main.save') }}</button>
</form>
@stop
