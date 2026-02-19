@extends('layout')

@section('title', __('payment::payments.orders'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'Payment']) }}">{{ __('payment::payments.payment') }}</a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.orders') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($orders->isNotEmpty())
        @foreach ($orders as $order)
        <div class="section mb-3 shadow">

            <div class="section-title">
                <h3>#{{ $order->id }} {{ $order->statusName() }}</h3>
            </div>

            Техническая информация:
            {{ bbCode('[spoiler][code]' . var_export($order->data, true) . '[/code][/spoiler]') }}


            <div class="section-content">
                <div class="section-message">
                    {{ __('main.comment') }}:
                    {{ bbCode($order->description) }}
                </div>

                <div>{{ $order->statusName() }}</div>
                <div>{{ __('payment::payments.order_number') }}: {{ $order->id }}</div>
                <div>{{ __('payment::payments.order_status') }}: {{ $order->statusName() }}</div>
                <div>{{ __('payment::payments.order_amount') }}: <strong>{{ $order->amount }} {{ $order->currency }}</strong></div>

                <div>{{ $order->email }}</div>
                <div>{{ $order->type }}</div>
            </div>

            <div class="section-body">



                <span class="avatar-micro">{{ $order->user->getAvatarImage() }}</span> {{ $order->user->exists ? $order->user->getProfile() : setting('guestsuser') }}
                <small class="section-date text-muted fst-italic">{{ dateFixed($order->created_at) }}</small>
            </div>
        </div>

        @endforeach
    @else
        {{ showError(__('payment::payments.empty_orders')) }}
    @endif

    {{ $orders->links() }}
@stop
