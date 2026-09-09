@extends('layout')

@section('title', __('game::games.your_turn'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/guess">{{ __('game::games.guess') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.your_turn') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($guessNumber !== $guess['number'])

        @if ($guess['count'] < 5)
            <span class="badge bg-info">{{ $guessNumber }}</span> — {!! $hint !!}<br><br>

            {!! __('game::games.guess_attempts', ['count' => '<b>' . $guess['count'] . '</b>']) !!}<br><br>
        @else
            <i class="fa fa-times"></i> <b class="text-danger">{{ __('game::games.guess_defeat') }}</b><br>
            {{ __('game::games.guess_number_was', ['number' => $guess['number']]) }}<br><br>

            <b>{{ __('game::games.guess_new_game') }}</b><br>
        @endif
    @else
        <b class="text-success">{{ __('game::games.guess_congratulations', ['number' => $guess['number']]) }}</b><br>
        {{ __('game::games.win_amount', ['money' => plural(100, setting('moneyname'))]) }}<br><br>

        <b>{{ __('game::games.guess_new_game') }}</b><br>
    @endif

    <b>{{ __('game::games.guess_enter_range') }}</b><br>
    <div class="section-form mb-3 shadow">
        <form action="/games/guess/go" method="post">
            @csrf
            <div class="mb-3{{ hasError('guess') }}">
                <label for="guess" class="form-label">{{ __('game::games.guess_enter') }}</label>
                <input class="form-control" name="guess" id="guess" value="{{ old('guess') }}" required>
                <div class="invalid-feedback">{{ textError('guess') }}</div>
            </div>

            <button class="btn btn-primary">{{ __('game::games.guess_button') }}</button>
        </form>
    </div>

    {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}<br>

    <i class="fa fa-arrow-circle-up"></i> <a href="/games/guess?new=1">{{ __('game::games.guess_restart') }}</a><br>
@stop
