@extends('layout')

@section('title', __('game::games.your_turn'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/safe">{{ __('game::games.safe') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.your_turn') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    {{ __('game::games.safe_combination') }}<br>
    <span class="badge bg-info">{{ $hack[0] }}</span>
    <span class="badge bg-info">{{ $hack[1] }}</span>
    <span class="badge bg-info">{{ $hack[2] }}</span>
    <span class="badge bg-info">{{ $hack[3] }}</span>
    <span class="badge bg-info">{{ $hack[4] }}</span>
    <br><br>

    @if (implode($safe['cipher']) === implode($hack))
        <img src="/assets/modules/games/safe/safe-open.png" alt="{{ __('game::games.safe_alt') }}"><br><br>
        {{ __('game::games.safe_success') }}<br>
        {{ __('game::games.safe_transferred', ['money' => plural($prize, setting('moneyname'))]) }}<br><br>

        <a href="/games/safe">{{ __('game::games.safe_again') }}</a><br><br>
    @else
        @if ($safe['try'])
            {{ __('game::games.safe_dont_rush', ['name' => $user->getName()]) }}<br>
            {{ __('game::games.safe_attempts_left', ['count' => $safe['try']]) }}<br>

            <img src="/assets/modules/games/safe/safe-closed.png" alt="{{ __('game::games.safe_alt') }}"><br>

            <div class="section-form mb-3 shadow">
                <form action="/games/safe/go" method="post">
                    @csrf
                    <div class="mb-3 row{{ hasError('bet') }}">
                        <div class="col-1">
                            <input class="form-control" name="code0" maxlength="1" value="{{ old('code0', $hack[0] === $safe['cipher'][0] ? $safe['cipher'][0] : '') }}" required>
                        </div>
                        <div class="col-1">
                            <input class="form-control" name="code1" maxlength="1" value="{{ old('code1', $hack[1] === $safe['cipher'][1] ? $safe['cipher'][1] : '') }}" required>
                        </div>
                        <div class="col-1">
                            <input class="form-control" name="code2" maxlength="1" value="{{ old('code2', $hack[2] === $safe['cipher'][2] ? $safe['cipher'][2] : '') }}" required>
                        </div>
                        <div class="col-1">
                            <input class="form-control" name="code3" maxlength="1" value="{{ old('code3', $hack[3] === $safe['cipher'][3] ? $safe['cipher'][3] : '') }}" required>
                        </div>
                        <div class="col-1">
                            <input class="form-control" name="code4" maxlength="1" value="{{ old('code4', $hack[4] === $safe['cipher'][4] ? $safe['cipher'][4] : '') }}" required>
                        </div>
                    </div>
                    <button class="btn btn-primary">{{ __('game::games.safe_button') }}</button>
                </form>
            </div>
        @else
            <img src="/assets/modules/games/safe/safe-closed.png" alt="{{ __('game::games.safe_alt') }}"><br>

            {{ __('game::games.safe_code_was') }}<br>
            <span class="badge bg-info">{{ $safe['cipher'][0] }}</span>
            <span class="badge bg-info">{{ $safe['cipher'][1] }}</span>
            <span class="badge bg-info">{{ $safe['cipher'][2] }}</span>
            <span class="badge bg-info">{{ $safe['cipher'][3] }}</span>
            <span class="badge bg-info">{{ $safe['cipher'][4] }}</span>
            <br><br>
            {{ __('game::games.safe_failed') }}<br>
            {{ __('game::games.safe_maybe_later') }}<br><br>

            <a href="/games/safe">{{ __('game::games.safe_one_more') }}</a><br><br>
        @endif
    @endif

    {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}<br>

    <hr>
    {{ __('game::games.safe_help') }}<br>
    {!! __('game::games.safe_help_absent', ['sign' => '<b>-</b>']) !!}<br>
    {!! __('game::games.safe_help_moved', ['sign' => '<b>*</b>']) !!}<br>
    {!! __('game::games.safe_help_exact', ['sign' => '<b>х</b>']) !!}<br>
@stop
