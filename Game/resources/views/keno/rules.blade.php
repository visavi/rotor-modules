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
        ['field' => $field, 'draw' => $draw, 'min' => $min, 'max' => $max] = $limits;
    @endphp

    {{ __('game::games.keno_intro', ['min' => $min, 'max' => $max, 'field' => $field, 'draw' => $draw]) }}<br><br>

    <b>{{ __('game::games.keno_payouts') }}</b>

    <div class="table-responsive my-3">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>{{ __('game::games.keno_marked') }}</th>
                    <th>{{ __('game::games.keno_hits') }}</th>
                    <th class="text-nowrap">{{ __('game::games.keno_frequency') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payouts as $picked => $table)
                    <tr>
                        <td class="fw-bold">{{ $picked }}</td>
                        <td>
                            @foreach ($table as $hits => $multiplier)
                                <span class="text-nowrap me-3">{{ $hits }} &mdash; x{{ $multiplier }}</span>
                            @endforeach
                        </td>
                        <td class="text-nowrap">{{ round($chances[$picked] * 100) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ __('game::games.keno_rules_edge') }}<br><br>

    <i class="fa fa-coins"></i> <a href="/games/keno">{{ __('game::games.keno') }}</a>
@stop
