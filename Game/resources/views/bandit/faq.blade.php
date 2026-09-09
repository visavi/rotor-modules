@extends('layout')

@section('title', __('game::games.rules'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/bandit">{{ __('game::games.slot') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.rules') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    {{ __('game::games.bandit_rules_simple') }}<br>
    {{ __('game::games.bandit_rules_price', ['money' => plural(5, setting('moneyname'))]) }}<br>
    {{ __('game::games.bandit_rules_payout') }}<br><br>
    {{ __('game::games.bandit_rules_combos') }}<br><br>
    {{ __('game::games.bandit_rules_list') }}<br>

    <img src="/assets/modules/games/bandit/1.gif" alt="image"> * {{ __('game::games.bandit_combo', ['symbol' => __('game::games.symbols_count.cherry'), 'money' => plural(10, setting('moneyname')), 'second' => 5]) }}<br>
    <img src="/assets/modules/games/bandit/2.gif" alt="image"> * {{ __('game::games.bandit_combo', ['symbol' => __('game::games.symbols_count.orange'), 'money' => plural(15, setting('moneyname')), 'second' => 10]) }}<br>
    <img src="/assets/modules/games/bandit/3.gif" alt="image"> * {{ __('game::games.bandit_combo', ['symbol' => __('game::games.symbols_count.grape'), 'money' => plural(25, setting('moneyname')), 'second' => 15]) }}<br>
    <img src="/assets/modules/games/bandit/4.gif" alt="image"> * {{ __('game::games.bandit_combo', ['symbol' => __('game::games.symbols_count.banana'), 'money' => plural(35, setting('moneyname')), 'second' => 25]) }}<br>
    <img src="/assets/modules/games/bandit/5.gif" alt="image"> * {{ __('game::games.bandit_combo', ['symbol' => __('game::games.symbols_count.apple'), 'money' => plural(50, setting('moneyname')), 'second' => 30]) }}<br>
    <img src="/assets/modules/games/bandit/6.gif" alt="image"> * {{ __('game::games.bandit_combo', ['symbol' => __('game::games.symbols_count.bar'), 'money' => plural(70, setting('moneyname')), 'second' => 50]) }}<br>
    <img src="/assets/modules/games/bandit/8.gif" alt="image"> * {{ __('game::games.bandit_combo', ['symbol' => __('game::games.symbols_count.dollar'), 'money' => plural(100, setting('moneyname')), 'second' => 60]) }}<br>
    <img src="/assets/modules/games/bandit/7.gif" alt="image"> * {{ __('game::games.bandit_combo_column', ['symbol' => __('game::games.symbols_count.seven'), 'money' => plural(177, setting('moneyname')), 'second' => 100]) }}<br>
    <img src="/assets/modules/games/bandit/7.gif" alt="image"> * {{ __('game::games.bandit_combo_row', ['symbol' => __('game::games.symbols_count.seven'), 'money' => plural(777, setting('moneyname')), 'second' => 177]) }}<br>
@stop
