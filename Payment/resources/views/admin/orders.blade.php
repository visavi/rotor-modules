@extends('layout')

@section('title', __('payment::payments.orders'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/admin">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="/admin/modules">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.orders') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="container">
        @if ($orders->isNotEmpty())
            <div class="mb-3">
                @foreach ($orders as $order)
                    <div class="row mb-3">
                        {{ $order->type }}<br>
                        {{ $order->amount }} {{ $order->currency}}

                        @if ($order->user_id)
                            {{ $order->user->getProfile() }}
                        @else
                            <span class="section-author fw-bold" data-login="{{ setting('guestsuser') }}">{{ setting('guestsuser') }}</span>
                        @endif
                        <small class="section-date text-muted fst-italic">{{ dateFixed($order->created_at) }}</small><br>
                    </div>
                @endforeach
            </div>
        @else
            {{ showError(__('payment::payments.admin.orders.empty_orders')) }}
        @endif

        {{ $orders->links() }}
    </div>
@stop
