@extends('layout')

@section('title', __('payment::payments.paid_adverts.renew_advert'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/payments/my">{{ __('payment::payments.paid_adverts.my_title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.paid_adverts.renew_advert') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @include('payment::advert/_renew_status')

    <div class="section-form mb-3 shadow">
        @include('payment::advert/_form')
    </div>
@stop
