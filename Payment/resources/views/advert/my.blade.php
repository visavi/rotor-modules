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
            @if ($advert->isCard())
                @include('payment::card/_card', ['card' => $advert->cardData(), 'layout' => 'feed'])

                <div class="mb-3 mt-n2">
                    {{ __('payment::payments.paid_adverts.place') }}: <b>{{ $advert->getPlaceName() }}</b>,
                    {{ __('payment::payments.paid_adverts.expires') }}:
                    <small class="section-date text-muted fst-italic">{{ dateFixed($advert->deleted_at) }}</small>
                    <a class="ms-2" href="/payments/my/card/{{ $advert->id }}" title="{{ __('main.edit') }}"><i class="fas fa-pencil-alt text-muted"></i></a>
                </div>
                @continue
            @endif

            <div class="section mb-3 shadow">
                <div class="section-title">
                    <i class="fas fa-globe-americas"></i>
                    <a href="{{ $advert->url }}" target="_blank" rel="nofollow">{{ $advert->names[0] }}</a>
                    @if (count($advert->names) > 1)
                        <span class="badge bg-info">{{ count($advert->names) }}</span>
                    @endif
                    <div class="float-end">
                        <a href="/payments/my/edit/{{ $advert->id }}" title="{{ __('main.edit') }}"><i class="fas fa-pencil-alt text-muted"></i></a>
                    </div>
                </div>

                <div class="section-body">
                    <div>{{ __('payment::payments.paid_adverts.place') }}: <b>{{ $advert->getPlaceName() }}</b></div>
                    <div>
                        {{ __('payment::payments.paid_adverts.expires') }}:
                        <small class="section-date text-muted fst-italic">{{ dateFixed($advert->deleted_at) }}</small>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        {{ showError(__('payment::payments.paid_adverts.my_empty')) }}
    @endif
@stop
