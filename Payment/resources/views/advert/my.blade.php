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
            <div class="section mb-3 shadow">
                <div class="section-title">
                    <i class="fas fa-globe-americas"></i>
                    <a href="{{ $advert->site }}" target="_blank" rel="nofollow">{{ $advert->names[0] }}</a>
                    @if (count($advert->names) > 1)
                        <span class="badge bg-info">{{ count($advert->names) }}</span>
                    @endif
                </div>

                <div class="section-body">
                    <div>{{ __('payment::payments.paid_adverts.place') }}: <b>{{ $advert->getPlaceName() }}</b></div>
                    <div>
                        {{ __('payment::payments.paid_adverts.expires') }}:
                        <small class="section-date text-muted fst-italic">{{ dateFixed($advert->deleted_at) }}</small>
                    </div>

                    <div class="mt-2">
                        <a class="btn btn-sm btn-primary" href="/payments/my/edit/{{ $advert->id }}">{{ __('main.edit') }}</a>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        {{ showError(__('payment::payments.paid_adverts.my_empty')) }}
    @endif
@stop
