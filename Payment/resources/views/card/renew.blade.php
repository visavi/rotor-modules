@extends('layout')

@section('title', __('payment::payments.cards.renew'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/payments/my">{{ __('payment::payments.paid_adverts.my_title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.cards.renew') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @include('payment::advert/_renew_status', ['advert' => $card])

    @if ($places)
        <div class="section-form mb-3 shadow">
            @include('payment::card/_form')
        </div>
    @else
        <div class="alert alert-info">{{ __('payment::payments.cards.no_places') }}</div>
    @endif
@stop
