@extends('layout')

@section('title', __('transfer::transfers.cash_transactions') . ' ' . $user->getName())

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="/admin/transfers">{{ __('transfer::transfers.cash_transactions') }}</a></li>
            <li class="breadcrumb-item active">{{ __('transfer::transfers.cash_transactions') }} {{ $user->getName() }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($transfers->isNotEmpty())
        @foreach ($transfers as $data)
            <div class="section mb-3 shadow">
                <div class="user-avatar">
                    {{ $data->user->getAvatar() }}
                    {{ $data->user->getOnline() }}
                </div>

                <div class="section-user d-flex align-items-start">
                    <div class="flex-grow-1">
                        {{ $data->user->getProfile() }}
                        <small class="section-date text-muted fst-italic">{{ dateFixed($data->created_at) }}</small><br>
                        <small class="fst-italic">{{ $data->user->getStatus() }}</small>
                    </div>

                    <div class="text-end">
                        @if ($data->user_id === $user->id)
                            <span class="badge bg-danger">{{ __('transfer::transfers.outgoing') }}</span>
                        @else
                            <span class="badge bg-success">{{ __('transfer::transfers.incoming') }}</span>
                        @endif
                    </div>
                </div>

                <div class="section-body border-top">
                    {{ __('transfer::transfers.transfer_for') }}: {{ $data->recipientUser->getProfile() }}<br>
                    {{ __('main.amount') }}: {{ plural($data->total, setting('moneyname')) }}<br>
                    {{ __('main.comment') }}: {{ renderHtml($data->text) }}<br>
                </div>
            </div>
        @endforeach

        {{ $transfers->links() }}

        <div class="mb-3">
            {{ __('main.total') }}: <b>{{ $transfers->total() }}</b>
        </div>
    @else
        {{ showError(__('transfer::transfers.empty_transfers')) }}
    @endif
@stop
