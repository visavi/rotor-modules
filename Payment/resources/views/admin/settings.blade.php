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
    <form method="post">
        @csrf

        <div class="mb-3{{ hasError('yookassa_shop_id') }}">
            <label for="yookassa_shop_id" class="form-label">{{ __('payment::payments.yookassa_shop_id') }}:</label>

            <input class="form-control" name="yookassa_shop_id" id="yookassa_shop_id" value="{{ old('yookassa_shop_id', $shopId) }}">
            <div class="invalid-feedback">{{ textError('yookassa_shop_id') }}</div>
        </div>

        <div class="mb-3{{ hasError('yookassa_secret_key') }}">
            <label for="yookassa_secret_key" class="form-label">{{ __('payment::payments.yookassa_secret_key') }}:</label>

            <input class="form-control" type="password" name="yookassa_secret_key" id="yookassa_secret_key" value="{{ old('yookassa_secret_key', $secretKey) }}" autocomplete="new-password">
            <div class="invalid-feedback">{{ textError('yookassa_secret_key') }}</div>
        </div>

        @foreach ($places as $place => $name)
            <div class="mb-3{{ hasError('prices.places.' . $place) }}">
                <label for="price_place_{{ $place }}" class="form-label">{{ $name }}:</label>

                <input class="form-control" name="prices[places][{{ $place }}]" id="price_place_{{ $place }}" value="{{ old('prices.places.' . $place, $prices['places'][$place]) }}" required>
                <div class="invalid-feedback">{{ textError('prices.places.' . $place) }}</div>
            </div>
        @endforeach

        <div class="mb-3{{ hasError('prices.colorPrice') }}">
            <label for="price_colorPrice" class="form-label">{{ __('payment::payments.paid_adverts.color') }}:</label>

            <input class="form-control" name="prices[colorPrice]" id="price_colorPrice" value="{{ old('prices.colorPrice', $prices['colorPrice']) }}" required>
            <div class="invalid-feedback">{{ textError('prices.colorPrice') }}</div>
        </div>

        <div class="mb-3{{ hasError('prices.boldPrice') }}">
            <label for="boldPrice" class="form-label">{{ __('payment::payments.paid_adverts.bold') }}:</label>

            <input class="form-control" name="prices[boldPrice]" id="price_boldPrice" value="{{ old('prices.boldPrice', $prices['boldPrice']) }}" required>
            <div class="invalid-feedback">{{ textError('prices.boldPrice') }}</div>
        </div>

        <div class="mb-3{{ hasError('prices.namePrice') }}">
            <label for="namePrice" class="form-label">{{ __('payment::payments.paid_adverts.name') }}:</label>

            <input class="form-control" name="prices[namePrice]" id="price_namePrice" value="{{ old('prices.namePrice', $prices['namePrice']) }}" required>
            <div class="invalid-feedback">{{ textError('prices.namePrice') }}</div>
        </div>

        <button class="btn btn-primary">{{ __('main.save' )}}</button>
    </form>
@stop
