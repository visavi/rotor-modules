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

        @foreach ($places as $place => $name)
            <div class="mb-3{{ hasError('prices.places.' . $place) }}">
                <label for="price_place_{{ $place }}" class="form-label">{{ $name }}:</label>

                <input class="form-control" name="prices[places][{{ $place }}]" id="price_place_{{ $place }}" value="{{ old('prices.places.' . $place, $prices['places'][$place]) }}" required>
                <div class="invalid-feedback">{{ textError('prices.places.' . $place) }}</div>
            </div>
        @endforeach

        <div class="mb-3{{ hasError('prices.colorPrice') }}">
            <label for="price_colorPrice" class="form-label">{{ __('admin.paid_adverts.color') }}:</label>

            <input class="form-control" name="prices[colorPrice]" id="price_colorPrice" value="{{ old('prices.colorPrice', $prices['colorPrice']) }}" required>
            <div class="invalid-feedback">{{ textError('prices.colorPrice') }}</div>
        </div>

        <div class="mb-3{{ hasError('prices.boldPrice') }}">
            <label for="boldPrice" class="form-label">{{ __('admin.paid_adverts.bold') }}:</label>

            <input class="form-control" name="prices[boldPrice]" id="price_boldPrice" value="{{ old('prices.boldPrice', $prices['boldPrice']) }}" required>
            <div class="invalid-feedback">{{ textError('prices.boldPrice') }}</div>
        </div>

        <div class="mb-3{{ hasError('prices.namePrice') }}">
            <label for="namePrice" class="form-label">{{ __('admin.paid_adverts.name') }}:</label>

            <input class="form-control" name="prices[namePrice]" id="price_namePrice" value="{{ old('prices.namePrice', $prices['namePrice']) }}" required>
            <div class="invalid-feedback">{{ textError('prices.namePrice') }}</div>
        </div>

        <button class="btn btn-primary">{{ __('main.save' )}}</button>
    </form>
@stop
