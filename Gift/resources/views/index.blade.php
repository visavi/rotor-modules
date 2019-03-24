@extends('layout')

@section('title')
    {{ trans('Gift::gifts.module') }}
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ trans('Gift::gifts.module') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="container">
        @if ($gifts->isNotEmpty())
            <div class="row">
                @foreach($gifts as $gift)
                    <div class="col">
                        <a href="/gifts/send/{{ $gift->id }}"><img src="{{ $gift->path }}" alt="{{ $gift->name }}"></a><br>
                        {{ $gift->price }}  {{ setting('currency') }}
                    </div>
                @endforeach
            </div>

            {!! pagination($page) !!}
        @else
            {!! showError(trans('Gift::gifts.empty_gifts')) !!}
        @endif
    </div>
@stop
