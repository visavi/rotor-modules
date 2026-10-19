@extends('layout')

@section('title', __('payment::payments.order_cost'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/payments/card">{{ __('payment::payments.cards.title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.order_cost') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <h2 class="h5">{{ __('payment::payments.cards.preview') }}</h2>

    <div class="row g-3 mb-3">
        <div class="col-md-8">
            @include('payment::card/_card', ['layout' => 'feed'])
        </div>
        <div class="col-md-4">
            <div class="section shadow">
                @include('payment::card/_card', ['layout' => 'sidebar'])
            </div>
        </div>
    </div>

    <div class="section mb-3 shadow">
        <div class="d-flex justify-content-between py-2 border-bottom">
            <span>{{ __('payment::payments.cards.place') }}: {{ __('payment::payments.cards.' . $card['place']) }}</span>
            <span>{{ plural($card['term'], __('main.plural_days')) }}</span>
        </div>
        <div class="py-2 border-bottom">{{ $card['email'] }}</div>
        <div class="d-flex justify-content-between align-items-start pt-2">
            <div class="h5 mb-0">{{ __('payment::payments.total_paid') }}:</div>
            <div class="h4 mb-0 text-primary">{{ number_format($card['prices']['total'], 0, ',', ' ') }} {{ setting('currency') }}</div>
        </div>
    </div>

    <form action="/payments/pay" method="post">
        @csrf
        <input type="hidden" name="data" value="{{ $data }}">
        <button class="btn btn-primary btn-lg w-100 py-3">
            <i class="fas fa-credit-card me-2"></i>
            {{ __('payment::payments.proceed_payment') }}
        </button>
    </form>
@stop
