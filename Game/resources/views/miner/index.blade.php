@extends('layout')

@section('title', __('game::games.miner'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.miner') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    {{ __('game::games.miner_intro') }}<br><br>

    <div class="section-form mb-3 shadow">
        <form action="/games/miner/bet" method="post">
            @csrf
            <div class="mb-3{{ hasError('bet') }}">
                <label for="bet" class="form-label">{{ __('game::games.bj_bet') }}</label>
                <input class="form-control" name="bet" id="bet" value="{{ old('bet') }}" required>
                <div class="invalid-feedback">{{ textError('bet') }}</div>
            </div>

            <div class="mb-3{{ hasError('mines') }}">
                <label for="mines" class="form-label">{{ __('game::games.miner_mines') }}</label>
                <select class="form-select" name="mines" id="mines">
                    @foreach ($mines as $count)
                        <option value="{{ $count }}"@selected(old('mines', $mines[0]) == $count)>{{ $count }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback">{{ textError('mines') }}</div>
            </div>

            <button class="btn btn-primary">{{ __('game::games.play') }}</button>
        </form>
    </div>

    {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}<br><br>

    {{ __('game::games.miner_rules') }}<br>
    {{ __('game::games.miner_rules_take') }}<br><br>

    {{ __('game::games.miner_table') }}<br>

    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>{{ __('game::games.miner_opened') }}</th>
                    @foreach ($mines as $count)
                        <th>{{ __('game::games.miner_mines_count', ['count' => $count]) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach (array_keys($rewards[$mines[0]]) as $opened)
                    <tr>
                        <td>{{ $opened }}</td>
                        @foreach ($mines as $count)
                            <td>{{ $rewards[$count][$opened] }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@stop
