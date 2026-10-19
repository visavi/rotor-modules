@extends('layout')

@section('title', __('payment::payments.cards.edit'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/payments/my">{{ __('payment::payments.paid_adverts.my_title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.cards.edit') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="alert alert-success">
        {{ $card->getPlaceName() }}, {{ __('payment::payments.paid_adverts.expires') }}: {{ dateFixed($card->deleted_at) }}
    </div>

    <div class="section-form mb-3 shadow">
        <form action="/payments/my/card/{{ $card->id }}" method="post" enctype="multipart/form-data">
            @csrf
            @include('payment::card/_edit_fields')

            <button class="btn btn-primary">{{ __('main.save') }}</button>
        </form>
    </div>
@stop
