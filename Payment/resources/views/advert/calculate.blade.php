@extends('layout')

@section('title', 'Покупка рекламы')

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/payments/advert">{{ __('admin.paid_adverts.create_advert') }}</a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.order_cost') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h2 class="h5 mb-0">{{ __('payment::payments.order_cost') }}</h2>
        </div>

        <div class="card-body">
            <div class="order-summary">
                <!-- Место размещения -->
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <div>
                        <i class="fas fa-map-marker-alt text-muted me-2"></i>
                        {{ __('admin.paid_adverts.place') }}: {{ __('admin.paid_adverts.' . $advert['place']) }}
                    </div>
                    <span class="text-primary fw-bold">{{ $advert['prices']['place'] }} {{ setting('currency') }}</span>
                </div>

                <!-- Адрес сайта -->
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <div>
                        <i class="fas fa-link text-muted me-2"></i>
                        {{ $advert['site'] }}
                    </div>
                    <a href="{{ $advert['site'] }}" target="_blank" class="text-decoration-none">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>

                <!-- Названия -->
                <div class="py-2 border-bottom">
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <i class="fas fa-heading text-muted me-2"></i>
                            {{ __('admin.paid_adverts.names') }} ({{ count($advert['names']) }})
                        </div>
                        <span class="text-primary fw-bold">{{ $advert['prices']['names'] }} {{ setting('currency') }}</span>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($advert['names'] as $name)
                            <li class="list-group-item small py-1">{{ $name }}</li>
                        @endforeach
                    </ul>
                </div>

                <!-- Цвет -->
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <div>
                        <i class="fas fa-palette text-muted me-2"></i>
                        {{ __('admin.paid_adverts.color') }}:

                        @if ($advert['color'])
                            <span class="color-badge" style="background-color: {{ $advert['color'] }};"></span>

                            {{ $advert['color'] }}
                        @else
                            {{ __('main.not_specified') }}
                        @endif
                    </div>
                    <span class="text-primary fw-bold">{{ $advert['prices']['color'] }} {{ setting('currency') }}</span>
                </div>

                <!-- Жирный текст -->
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <div>
                        <i class="fas fa-bold text-muted me-2"></i>
                        {{ __('admin.paid_adverts.bold') }}: {{ $advert['bold'] ? 'Да' : 'Нет' }}
                    </div>
                    <span class="text-primary fw-bold">{{ $advert['prices']['bold'] }} {{ setting('currency') }}</span>
                </div>

                <!-- Срок -->
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <div>
                        <i class="far fa-calendar-alt text-muted me-2"></i>
                        {{ __('admin.paid_adverts.term') }}
                    </div>
                    <span>{{ plural($advert['term'], __('main.plural_days')) }}</span>
                </div>

                <!-- Комментарий -->
                @if ($advert['comment'])
                    <div class="py-2 border-bottom">
                        <div class="d-flex align-items-center text-muted mb-1">
                            <i class="far fa-comment me-2"></i>
                            {{ __('main.comment') }}
                        </div>
                        <div class="p-2 rounded small">
                            {{ bbCode($advert['comment']) }}
                        </div>
                    </div>
                @endif
            </div>

            <!-- Итого -->
            <div class="total-block p-3 rounded mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="h5 mb-0">{{ __('payment::payments.total_paid') }}:</div>
                    <div class="h4 mb-0 text-primary">{{ number_format($advert['prices']['total'], 0, ',', ' ') }} {{ setting('currency') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-footer border-0">
        <form action="/payments/pay" method="post">
            @csrf
            <input type="hidden" name="data" value="{{ $data }}">
            <button class="btn btn-primary btn-lg w-100 py-3">
                <i class="fas fa-credit-card me-2"></i>
                {{ __('payment::payments.proceed_payment') }}
            </button>
        </form>
    </div>
@stop

@push('styles')
    <style>
        .order-summary i {
            width: 20px;
            text-align: center;
        }
        .color-badge {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 3px;
            vertical-align: middle;
            margin-right: 5px;
            border: 1px solid #dee2e6;
        }
    </style>
@endpush
