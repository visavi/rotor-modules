@extends('layout')

@section('title', __('payment::payments.cards.title'))

@section('header')
    @if (auth()->check())
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
            <li class="breadcrumb-item active">{{ __('payment::payments.cards.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @include('payment::advert/_tabs')

    @if (auth()->guest())
        <div class="alert alert-info">
            {{ __('payment::payments.cards.login_required') }}.
            <a href="{{ route('login') }}">{{ __('payment::payments.cards.login_link') }}</a>
        </div>
    @elseif ($places)
        <div class="section-form mb-3 shadow">
            @include('payment::card/_form')
        </div>
    @else
        <div class="alert alert-info">{{ __('payment::payments.cards.no_places') }}</div>
    @endif
@stop
