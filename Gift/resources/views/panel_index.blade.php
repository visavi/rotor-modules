@extends('layout')

@section('title')
    {{ trans('Gift::gifts.title') }}
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/admin">{{ trans('main.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ trans('Gift::gifts.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="container">
        @if ($gifts->isNotEmpty())
            <form action="/admin/gifts" method="post">
                <input type="hidden" name="token" value="{{ $_SESSION['token'] }}">

                <div class="row">
                    @foreach($gifts as $gift)
                        <div class="col-md-2 col-sm-3">
                            <img src="{{ $gift->path }}" alt="{{ $gift->name }}">
                        </div>
                        <div class="col-md-2 col-sm-3">
                            <label for="gift_{{ $gift->id }}">{{ trans('Gift::gifts.price') }} ({{ setting('currency') }}):</label>
                            <input class="form-control" name="gifts[{{ $gift->id }}]" id="gift_{{ $gift->id }}" maxlength="10" value="{{ $gift->price }}"><br>
                        </div>
                    @endforeach
                </div>
                <button class="btn btn-primary">{{ trans('main.save') }}</button>
            </form>

            {!! pagination($page) !!}
        @else
            {!! showError(trans('Gift::gifts.empty_gifts')) !!}
        @endif
    </div>
@stop
