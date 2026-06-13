@extends('layout')

@section('title', 'Покупка рекламы')

@section('header')
    @if (getUser())
        <div class="float-end">
            <a class="btn btn-adaptive" href="/payments/my">{{ __('payment::payments.paid_adverts.my_title') }}</a>
        </div>
    @endif

    <h1>{{ __('payment::payments.paid_adverts.create_advert') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.paid_adverts.create_advert') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section-form mb-3 shadow">
        @include('payment::advert/_form')
    </div>
@stop
