@extends('layout')

@section('title', __('Gift::gifts.title'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('Gift::gifts.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="container">
        @if ($gifts->isNotEmpty())
            <div class="row mb-3">
                @foreach ($gifts as $gift)
                    <div class="col">
                        <a href="/gifts/send/{{ $gift->id }}"><img src="{{ $gift->path }}" alt="{{ $gift->name }}"></a><br>
                        {{ $gift->price }}  {{ setting('currency') }}
                    </div>
                @endforeach
            </div>
        @else
            {{ showError(__('Gift::gifts.empty_gifts')) }}
        @endif

        {{ $gifts->links() }}
    </div>
@stop
