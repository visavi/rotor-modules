@extends('layout')

@section('title')
    {{ __('Gift::gifts.send_gift') }}
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active"><a href="/gifts">{{ __('Gift::gifts.title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Gift::gifts.send_gift') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="form">
        <form action="/gifts/send/{{ $gift->id }}" method="post">
            @csrf
            @if ($user)
                <i class="fas fa-gift"></i> {{ __('Gift::gifts.gift_for') }} <b>{{ $user->login }}</b>:<br><br>
                <input type="hidden" name="user" value="{{ $user->login }}">
            @else
                <div class="form-group{{ hasError('user') }}">
                    <label for="user">{{ __('main.user_login') }}:</label>
                    <input name="user" class="form-control" id="user" maxlength="20" placeholder="{{ __('main.user_login') }}" value="{{ getInput('user') }}" required>
                    <div class="invalid-feedback">{{ textError('user') }}</div>
                </div>
            @endif

            <div class="form-group{{ hasError('msg') }}">
                <label for="msg">{{ __('main.message') }}:</label>
                <textarea class="form-control markItUp" maxlength="1000" id="msg" rows="5" name="msg" placeholder="{{ __('main.message') }}">{{ getInput('msg') }}</textarea>
                <div class="invalid-feedback">{{ textError('msg') }}</div>
                <span class="js-textarea-counter"></span>
            </div>

            <div>
                <a href="/gifts/send/{{ $gift->id }}"><img src="{{ $gift->path }}" alt="{{ $gift->name }}"></a><br>
                {{ __('Gift::gifts.price') }}: <span class="badge badge-primary">{{ $gift->price }}  {{ setting('currency') }}</span>
            </div>

            <button class="btn btn-primary">{{ __('main.send') }}</button>
        </form>
    </div>
@stop
