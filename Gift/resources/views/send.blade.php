@extends('layout')

@section('title')
    {{ trans('Gift::gifts.send_gift') }}
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active"><a href="/gifts">{{ trans('Gift::gifts.title') }}</a></li>
            <li class="breadcrumb-item active">{{ trans('Gift::gifts.send_gift') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="form">
        <form action="/gifts/send/{{ $gift->id }}" method="post">
            <input type="hidden" name="token" value="{{ $_SESSION['token'] }}">

            @if ($user)
                <i class="fas fa-gift"></i> {{ trans('Gift::gifts.gift_for') }} <b>{{ $user->login }}</b>:<br><br>
                <input type="hidden" name="user" value="{{ $user->login }}">
            @else
                <div class="form-group{{ hasError('user') }}">
                    <label for="user">{{ trans('main.user_login') }}:</label>
                    <input name="user" class="form-control" id="user" maxlength="20" placeholder="{{ trans('main.user_login') }}" value="{{ getInput('user') }}" required>
                    {!! textError('user') !!}
                </div>
            @endif

            <div class="form-group{{ hasError('msg') }}">
                <label for="msg">{{ trans('main.message') }}:</label>
                <textarea class="form-control markItUp" maxlength="1000" id="msg" rows="5" name="msg" placeholder="{{ trans('main.message') }}">{{ getInput('msg') }}</textarea>
                <span class="js-textarea-counter"></span>
                {!! textError('msg') !!}
            </div>

            <div>
                <a href="/gifts/send/{{ $gift->id }}"><img src="{{ $gift->path }}" alt="{{ $gift->name }}"></a><br>
                {{ trans('Gift::gifts.price') }}: <span class="badge badge-primary">{{ $gift->price }}  {{ setting('currency') }}</span>
            </div>

            <button class="btn btn-primary">{{ trans('main.send') }}</button>
        </form>
    </div>
@stop
