@extends('layout')

@section('title', __('Lottery::lottery.title'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('Lottery::lottery.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section mb-3 shadow">
        <div class="mb-3">
            {!! __('Lottery::lottery.lottery_info') !!}
        </div>
        <div>
            {{ __('Lottery::lottery.jackpot_amount', ['jackpot' => plural($today->amount, setting('moneyname'))]) }}
        </div>
        @if ($yesterday)
            <div class="mt-3">
                {{ __('Lottery::lottery.winning_number', ['number' => $yesterday->number]) }}<br>

                @if ($yesterday->winners->isNotEmpty())
                    <i class="fas fa-crown"></i> {{ __('Lottery::lottery.winners') }}:
                    @foreach ($yesterday->winners as $key => $winner)
                        @php $comma = (empty($key)) ? '' : ', '; @endphp
                        {{ $comma }}{{ $winner->user->getProfile() }}
                    @endforeach
                @else
                    <div class="alert alert-info">
                        {{ __('Lottery::lottery.jackpot_not_win') }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if ($user = getUser())
        <div class="section-form mb-3 shadow">

            @if (! $ticket)
                <form action="/lottery/buy" method="post" class="mb-3">
                    @csrf
                    <div class="form-group{{ hasError('number') }}">
                        <label for="number">{{ __('Lottery::lottery.enter_number') }}:</label>
                        <input type="text" class="form-control" id="number" name="number" maxlength="3" value="{{ getInput('number') }}" placeholder="{{ __('Lottery::lottery.enter_number_inclusive', ['min' => $config['numberRange'][0], 'max' => $config['numberRange'][1]]) }}" required>
                        <div class="invalid-feedback">{{ textError('number') }}</div>
                    </div>

                    <button class="btn btn-primary">{{ __('Lottery::lottery.buy_ticket') }}</button>
                </form>
            @else
                <div class="alert alert-success">
                    {{ __('Lottery::lottery.ticket_purchased', ['number' => $ticket->number]) }}
                </div>
            @endif

            <div class="mb-3">
                {{ __('Lottery::lottery.participate') }}: {{ $today->lotteryUsers()->count() }}<br>
                {{ __('Lottery::lottery.ticket_price') }}: {{ plural($config['ticketPrice'], setting('moneyname')) }}<br>
                {{ __('Lottery::lottery.in_stock') }}: {{ plural($user->money, setting('moneyname')) }}
            </div>

            <i class="fa fa-question-circle"></i> <a href="#" onclick="return showLotteryUsers()">{{ __('Lottery::lottery.participants') }}</a><br>
        </div>
    @endif

    <div class="section mb-3 shadow js-lottery-users" style="display: none">
        <h5>{{ __('Lottery::lottery.participants') }}:</h5>
        @if ($today->lotteryUsers->isNotEmpty())
            @foreach ($today->lotteryUsers as $lotteryUser)
                {{ $lotteryUser->user->getProfile() }} Ставка: {!! $lotteryUser->number !!}<br>
            @endforeach
        @else
            <div class="alert alert-info">
                {{ __('Lottery::lottery.no_participants') }}
            </div>
        @endif
    </div>
@stop

@push('scripts')
    <script>
        function showLotteryUsers() {
            $('.js-lottery-users').slideToggle();
            return false;
        }
    </script>
@endpush
