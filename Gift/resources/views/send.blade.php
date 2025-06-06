@extends('layout')

@section('title', __('gift::gifts.send_gift'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active"><a href="/gifts">{{ __('gift::gifts.title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('gift::gifts.send_gift') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section-form mb-3 shadow">
        <form action="/gifts/send/{{ $gift->id }}" method="post">
            @csrf
            @if ($user)
                <i class="fas fa-gift"></i> {{ __('gift::gifts.gift_for') }} <b>{{ $user->getProfile() }}</b>:<br><br>
                <input type="hidden" name="user" value="{{ $user->login }}">
            @else
                <div class="mb-3{{ hasError('user') }}">
                    <label for="user" class="form-label">{{ __('main.user_login') }}:</label>
                    <input name="user" class="form-control" id="user" maxlength="20" placeholder="{{ __('main.user_login') }}" value="{{ getInput('user') }}" required>
                    <div class="invalid-feedback">{{ textError('user') }}</div>
                </div>
            @endif

            <div class="mb-3{{ hasError('msg') }}">
                <label for="msg" class="form-label">{{ __('main.message') }}:</label>
                <textarea class="form-control markItUp" maxlength="1000" id="msg" rows="5" name="msg" placeholder="{{ __('main.message') }}">{{ getInput('msg') }}</textarea>
                <div class="invalid-feedback">{{ textError('msg') }}</div>
                <span class="js-textarea-counter"></span>
            </div>

            <div class="mb-3">
                <a href="/gifts/send/{{ $gift->id }}"><img src="{{ $gift->path }}" alt="{{ $gift->name }}"></a><br>
                {{ __('gift::gifts.price') }}: <span class="badge bg-primary">{{ $gift->price }} {{ setting('currency') }}</span>
            </div>

            <button class="btn btn-primary">{{ __('main.send') }}</button>
        </form>
    </div>
@stop
