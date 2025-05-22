@extends('layout')

@section('title', 'Покупка рекламы')

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/payments/advert">{{ __('admin.paid_adverts.create_advert') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Payment::payments.order_status') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @php $statusMessage = $order->statusMessage(); @endphp

    <div class="section my-3 shadow">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h1 class="mb-4 {{ $statusMessage['style'] }}">{{ $statusMessage['title'] }}</h1>
                <p class="mb-4">{{ $statusMessage['message'] }}</p>
                <p class="mb-2">{{ __('Payment::payments.order_number') }}: {{ $order->id }}</p>
                <p class="mb-2">{{ __('Payment::payments.order_status') }}: {{ $order->statusName() }}</p>
                <p class="fs-4">{{ __('Payment::payments.order_amount') }}: <strong>{{ $order->amount }} {{ $order->currency }}</strong></p>
            </div>
        </div>
    </div>
@stop
