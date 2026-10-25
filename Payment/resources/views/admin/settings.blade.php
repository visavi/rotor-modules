@extends('admin/settings/layout')

@section('title', __('payment::payments.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'payment']) }}">{{ __('admin.modules.module') }} {{ __('payment::payments.payment') }}</a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.settings') }}</li>
        </ol>
    </nav>
@stop

@section('settings')
    <form method="post" action="{{ route('payment.settings.update') }}">
        @csrf

        <div class="mb-3{{ hasError('sets.payment_yookassa_shop_id') }}">
            <label for="payment_yookassa_shop_id" class="form-label">{{ __('payment::payments.yookassa_shop_id') }}:</label>

            <input class="form-control" name="sets[payment_yookassa_shop_id]" id="payment_yookassa_shop_id" value="{{ old('sets.payment_yookassa_shop_id', $settings['payment_yookassa_shop_id'] ?? '') }}">
            <div class="invalid-feedback">{{ textError('sets.payment_yookassa_shop_id') }}</div>
        </div>

        <div class="mb-3{{ hasError('sets.payment_yookassa_secret_key') }}">
            <label for="payment_yookassa_secret_key" class="form-label">{{ __('payment::payments.yookassa_secret_key') }}:</label>

            <input class="form-control" type="password" name="sets[payment_yookassa_secret_key]" id="payment_yookassa_secret_key" value="{{ old('sets.payment_yookassa_secret_key', $settings['payment_yookassa_secret_key'] ?? '') }}" autocomplete="new-password">
            <div class="invalid-feedback">{{ textError('sets.payment_yookassa_secret_key') }}</div>
        </div>

        @foreach ($places as $place => $name)
            <div class="mb-3{{ hasError('sets.payment_price_' . $place) }}">
                <label for="payment_price_{{ $place }}" class="form-label">{{ $name }}:</label>

                <input type="number" class="form-control" name="sets[payment_price_{{ $place }}]" id="payment_price_{{ $place }}" min="0" value="{{ old('sets.payment_price_' . $place, $settings['payment_price_' . $place] ?? 0) }}" required>
                <div class="invalid-feedback">{{ textError('sets.payment_price_' . $place) }}</div>
            </div>
        @endforeach

        <div class="mb-3{{ hasError('sets.payment_price_color') }}">
            <label for="payment_price_color" class="form-label">{{ __('payment::payments.paid_adverts.color') }}:</label>

            <input type="number" class="form-control" name="sets[payment_price_color]" id="payment_price_color" min="0" value="{{ old('sets.payment_price_color', $settings['payment_price_color'] ?? 0) }}" required>
            <div class="invalid-feedback">{{ textError('sets.payment_price_color') }}</div>
        </div>

        <div class="mb-3{{ hasError('sets.payment_price_bold') }}">
            <label for="payment_price_bold" class="form-label">{{ __('payment::payments.paid_adverts.bold') }}:</label>

            <input type="number" class="form-control" name="sets[payment_price_bold]" id="payment_price_bold" min="0" value="{{ old('sets.payment_price_bold', $settings['payment_price_bold'] ?? 0) }}" required>
            <div class="invalid-feedback">{{ textError('sets.payment_price_bold') }}</div>
        </div>

        <div class="mb-3{{ hasError('sets.payment_price_name') }}">
            <label for="payment_price_name" class="form-label">{{ __('payment::payments.paid_adverts.name') }}:</label>

            <input type="number" class="form-control" name="sets[payment_price_name]" id="payment_price_name" min="0" value="{{ old('sets.payment_price_name', $settings['payment_price_name'] ?? 0) }}" required>
            <div class="invalid-feedback">{{ textError('sets.payment_price_name') }}</div>
        </div>

        <h5 class="mt-4">{{ __('payment::payments.balance.title') }}</h5>

        <div class="form-check form-switch mb-3">
            <input type="hidden" value="0" name="sets[payment_balance_enabled]">
            <input type="checkbox" role="switch" class="form-check-input" value="1" name="sets[payment_balance_enabled]" id="payment_balance_enabled"{{ old('sets.payment_balance_enabled', $settings['payment_balance_enabled'] ?? 0) ? ' checked' : '' }}>
            <label class="form-check-label" for="payment_balance_enabled">{{ __('payment::payments.balance.enabled') }}</label>
        </div>

        <div class="mb-3{{ hasError('sets.payment_balance_rate') }}">
            <label for="payment_balance_rate" class="form-label">{{ __('payment::payments.balance.setting_rate', ['currency' => setting('currency')]) }}:</label>

            <input type="number" class="form-control" name="sets[payment_balance_rate]" id="payment_balance_rate" min="1" value="{{ old('sets.payment_balance_rate', $settings['payment_balance_rate'] ?? 100) }}" required>
            <div class="invalid-feedback">{{ textError('sets.payment_balance_rate') }}</div>
        </div>

        <div class="mb-3{{ hasError('sets.payment_balance_min') }}">
            <label for="payment_balance_min" class="form-label">{{ __('payment::payments.balance.setting_min', ['currency' => setting('currency')]) }}:</label>

            <input type="number" class="form-control" name="sets[payment_balance_min]" id="payment_balance_min" min="1" value="{{ old('sets.payment_balance_min', $settings['payment_balance_min'] ?? 10) }}" required>
            <div class="invalid-feedback">{{ textError('sets.payment_balance_min') }}</div>
        </div>

        <h5 class="mt-4">{{ __('payment::payments.cards.title') }}</h5>

        @foreach (\Modules\Payment\Models\PaidAdvert::CARD_PLACES as $place)
            <div class="mb-3{{ hasError('sets.payment_price_' . $place) }}">
                <label for="payment_price_{{ $place }}" class="form-label">{{ __('payment::payments.cards.' . $place) }}, {{ setting('currency') }} {{ __('payment::payments.paid_adverts.per_day') }}:</label>

                <input type="number" class="form-control" name="sets[payment_price_{{ $place }}]" id="payment_price_{{ $place }}" min="0" value="{{ old('sets.payment_price_' . $place, $settings['payment_price_' . $place] ?? 0) }}" required>
                <div class="invalid-feedback">{{ textError('sets.payment_price_' . $place) }}</div>
                <div class="form-text">{{ __('payment::payments.cards.price_hint') }}</div>
            </div>
        @endforeach

        <div class="mb-3{{ hasError('sets.payment_card_limit') }}">
            <label for="payment_card_limit" class="form-label">{{ __('payment::payments.cards.setting_limit') }}:</label>

            <input type="number" class="form-control" name="sets[payment_card_limit]" id="payment_card_limit" min="1" value="{{ old('sets.payment_card_limit', $settings['payment_card_limit'] ?? 3) }}" required>
            <div class="invalid-feedback">{{ textError('sets.payment_card_limit') }}</div>
        </div>


        <button class="btn btn-primary">{{ __('main.save' )}}</button>
    </form>
@stop
