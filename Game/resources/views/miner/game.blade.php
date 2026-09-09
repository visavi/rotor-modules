@extends('layout')

@section('title', __('game::games.miner'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/miner">{{ __('game::games.miner') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.your_turn') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}<br><br>

    <div class="d-flex flex-wrap gap-3 mb-3">
        <div>{{ __('game::games.miner_bet', ['money' => plural($miner['bet'], setting('moneyname'))]) }}</div>
        <div>{{ __('game::games.miner_mines_count', ['count' => $miner['mines']]) }}</div>
        <div>{{ __('game::games.miner_opened_count', ['count' => count($miner['opened'])]) }}</div>
    </div>

    <form action="/games/miner/go" method="post">
        @csrf

        {{-- Клетки — кнопки одной формы: по ссылкам браузер ходит сам при предзагрузке --}}
        <div class="d-grid gap-1 mb-3" style="grid-template-columns: repeat(5, minmax(0, 1fr)); max-width: 20rem;">
            @for ($cell = 0; $cell < $cells; $cell++)
                @php
                    $isOpen = in_array($cell, $miner['opened'], true);
                    $isMine = in_array($cell, $miner['field'], true);
                    $reveal = $miner['status'] !== null;
                @endphp

                @if ($isOpen && $isMine)
                    <span class="btn btn-danger disabled"><i class="fas fa-bomb"></i></span>
                @elseif ($isOpen)
                    <span class="btn btn-success disabled"><i class="fas fa-gem"></i></span>
                @elseif ($reveal && $isMine)
                    <span class="btn btn-outline-danger disabled"><i class="fas fa-bomb"></i></span>
                @elseif ($reveal)
                    <span class="btn btn-outline-secondary disabled">&nbsp;</span>
                @else
                    <button class="btn btn-secondary" name="cell" value="{{ $cell }}">&nbsp;</button>
                @endif
            @endfor
        </div>
    </form>

    @if ($miner['status'] === 'lost')
        <div class="my-3 fw-bold">
            <span class="text-danger">{{ __('game::games.miner_boom') }}</span><br>
            {{ __('game::games.bj_lost', ['money' => plural($miner['bet'], setting('moneyname'))]) }}
        </div>

        <form action="/games/miner/bet" method="post" class="d-inline">
            @csrf
            <input type="hidden" name="bet" value="{{ $miner['bet'] }}">
            <input type="hidden" name="mines" value="{{ $miner['mines'] }}">
            <button class="btn btn-primary">{{ __('game::games.bj_repeat') }}</button>
        </form>
        <br><br>

        <i class="fa fa-coins"></i> <a href="/games/miner">{{ __('game::games.miner_new_game') }}</a><br>
    @elseif ($miner['status'] === 'won')
        <div class="my-3 fw-bold">
            <span class="text-success">{{ __('game::games.victory') }}</span><br>
            {{ __('game::games.bj_won', ['money' => plural($reward, setting('moneyname'))]) }}
        </div>

        <form action="/games/miner/bet" method="post" class="d-inline">
            @csrf
            <input type="hidden" name="bet" value="{{ $miner['bet'] }}">
            <input type="hidden" name="mines" value="{{ $miner['mines'] }}">
            <button class="btn btn-primary">{{ __('game::games.bj_repeat') }}</button>
        </form>
        <br><br>

        <i class="fa fa-coins"></i> <a href="/games/miner">{{ __('game::games.miner_new_game') }}</a><br>
    @else
        @if ($miner['opened'])
            <form action="/games/miner/cash" method="post" class="d-inline">
                @csrf
                <button class="btn btn-success">
                    {{ __('game::games.miner_cash', ['money' => plural($reward, setting('moneyname'))]) }}
                </button>
            </form>
            <br><br>
        @endif

        {{ __('game::games.miner_next', ['money' => plural($next, setting('moneyname'))]) }}<br>
    @endif
@stop
