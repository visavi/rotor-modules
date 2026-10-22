@extends('layout')

@section('title', __('payment::payments.paid_adverts.my_title'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.paid_adverts.my_title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @foreach ($pendingOrders as $order)
        <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span>
                {{ __('payment::payments.order_number') }} {{ $order->id }} ({{ $order->amount }} {{ $order->currency }}) — {{ $order->statusName() }}
            </span>
            <a class="btn btn-sm btn-primary" href="{{ $order->payment_url }}" rel="nofollow">{{ __('payment::payments.paid_adverts.continue_payment') }}</a>
        </div>
    @endforeach

    @if ($adverts->isNotEmpty())
        @foreach ($adverts as $advert)
            @include('payment::advert/_my_advert')
        @endforeach
    @else
        {{ showError(__('payment::payments.paid_adverts.my_empty')) }}
    @endif

    @if ($expired->isNotEmpty())
        <h2 class="h5 mt-4">{{ __('payment::payments.paid_adverts.expired_title') }}</h2>

        @foreach ($expired as $advert)
            @include('payment::advert/_my_advert')
        @endforeach
    @endif
@stop
