@extends('layout')

@section('title', __('game::games.keno') . ' - ' . __('game::games.rules'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/keno">{{ __('game::games.keno') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.rules') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @php
        ['field' => $field, 'draw' => $draw, 'picks' => $picks] = $limits;
    @endphp

    {{ __('game::games.keno_intro', ['picks' => $picks, 'field' => $field, 'draw' => $draw]) }}<br><br>

    <b>{{ __('game::games.keno_payouts') }}</b>

    <div class="table-responsive my-3">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>{{ __('game::games.keno_hits') }}</th>
                    <th>{{ __('game::games.keno_prize') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payouts as $hits => $multiplier)
                    <tr>
                        <td class="fw-bold text-nowrap">{{ __('game::games.keno_hits_of', ['hits' => $hits, 'picks' => $picks]) }}</td>
                        <td class="text-nowrap"><b>x{{ $multiplier }}</b></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ __('game::games.keno_rules_edge') }}<br><br>

    <i class="fa fa-coins"></i> <a href="/games/keno">{{ __('game::games.keno') }}</a>
@stop
