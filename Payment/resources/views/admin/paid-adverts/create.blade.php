@extends('layout')

@section('title', __('payment::payments.paid_adverts.create_advert'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active"><a href="/admin/paid-adverts">{{ __('payment::payments.paid_adverts') }}</a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.paid_adverts.create_advert') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section-form mb-3 shadow">
        @include('payment::admin/paid-adverts/_form')
    </div>
@stop
