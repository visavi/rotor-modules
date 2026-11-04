@extends('layout')

@section('title', __('gift::gifts.title'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('gift::gifts.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="container">
        @if ($gifts->isNotEmpty())
            {{-- Колонок столько, чтобы 24 подарка на странице делились без остатка: ряды полные на любом экране --}}
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3 mb-3 text-center">
                @foreach ($gifts as $gift)
                    <div class="col">
                        <a href="/gifts/send/{{ $gift->id }}?user={{ $user }}"><img src="{{ $gift->path }}" alt="{{ $gift->name }}"></a><br>
                        {{ plural($gift->price, setting('moneyname')) }}
                    </div>
                @endforeach
            </div>
        @else
            {{ showError(__('gift::gifts.empty_gifts')) }}
        @endif

        {{ $gifts->links() }}
    </div>
@stop
