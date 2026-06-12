@extends('layout')

@section('title', __('payment::payments.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'Payment']) }}">{{ __('payment::payments.payment') }}</a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.settings') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <form method="post" action="{{ route('payment.settings.update') }}">
        @csrf

        <div class="mb-3{{ hasError('sets[payment_yookassa_shop_id]') }}">
            <label for="payment_yookassa_shop_id" class="form-label">{{ __('payment::payments.yookassa_shop_id') }}:</label>

            <input class="form-control" name="sets[payment_yookassa_shop_id]" id="payment_yookassa_shop_id" value="{{ getInput('sets.payment_yookassa_shop_id', $settings['payment_yookassa_shop_id'] ?? '') }}">
            <div class="invalid-feedback">{{ textError('sets[payment_yookassa_shop_id]') }}</div>
        </div>

        <div class="mb-3{{ hasError('sets[payment_yookassa_secret_key]') }}">
            <label for="payment_yookassa_secret_key" class="form-label">{{ __('payment::payments.yookassa_secret_key') }}:</label>

            <input class="form-control" type="password" name="sets[payment_yookassa_secret_key]" id="payment_yookassa_secret_key" value="{{ getInput('sets.payment_yookassa_secret_key', $settings['payment_yookassa_secret_key'] ?? '') }}" autocomplete="new-password">
            <div class="invalid-feedback">{{ textError('sets[payment_yookassa_secret_key]') }}</div>
        </div>

        @foreach ($places as $place => $name)
            <div class="mb-3{{ hasError('sets[payment_price_' . $place . ']') }}">
                <label for="payment_price_{{ $place }}" class="form-label">{{ $name }}:</label>

                <input type="number" class="form-control" name="sets[payment_price_{{ $place }}]" id="payment_price_{{ $place }}" min="0" value="{{ getInput('sets.payment_price_' . $place, $settings['payment_price_' . $place] ?? 0) }}" required>
                <div class="invalid-feedback">{{ textError('sets[payment_price_' . $place . ']') }}</div>
            </div>
        @endforeach

        <div class="mb-3{{ hasError('sets[payment_price_color]') }}">
            <label for="payment_price_color" class="form-label">{{ __('payment::payments.paid_adverts.color') }}:</label>

            <input type="number" class="form-control" name="sets[payment_price_color]" id="payment_price_color" min="0" value="{{ getInput('sets.payment_price_color', $settings['payment_price_color'] ?? 0) }}" required>
            <div class="invalid-feedback">{{ textError('sets[payment_price_color]') }}</div>
        </div>

        <div class="mb-3{{ hasError('sets[payment_price_bold]') }}">
            <label for="payment_price_bold" class="form-label">{{ __('payment::payments.paid_adverts.bold') }}:</label>

            <input type="number" class="form-control" name="sets[payment_price_bold]" id="payment_price_bold" min="0" value="{{ getInput('sets.payment_price_bold', $settings['payment_price_bold'] ?? 0) }}" required>
            <div class="invalid-feedback">{{ textError('sets[payment_price_bold]') }}</div>
        </div>

        <div class="mb-3{{ hasError('sets[payment_price_name]') }}">
            <label for="payment_price_name" class="form-label">{{ __('payment::payments.paid_adverts.name') }}:</label>

            <input type="number" class="form-control" name="sets[payment_price_name]" id="payment_price_name" min="0" value="{{ getInput('sets.payment_price_name', $settings['payment_price_name'] ?? 0) }}" required>
            <div class="invalid-feedback">{{ textError('sets[payment_price_name]') }}</div>
        </div>

        <button class="btn btn-primary">{{ __('main.save' )}}</button>
    </form>
@stop
