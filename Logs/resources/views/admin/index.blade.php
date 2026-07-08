@extends('layout')

@section('title', __('logs::logs.title'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('logs::logs.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($logs->isNotEmpty())
        @foreach ($logs as $log)
            <div class="section mb-3 shadow">
                <div class="user-avatar">
                    {{ $log->user->getAvatar() }}
                    {{ $log->user->getOnline() }}
                </div>

                <div class="section-user d-flex align-items-start">
                    <div class="flex-grow-1">
                        {{ $log->user->getProfile() }}
                        <small class="section-date text-muted fst-italic">{{ dateFixed($log->created_at) }}</small>
                    </div>
                </div>

                <div class="section-body border-top">
                    {{ __('logs::logs.page') }}: {{ $log->request }}<br>
                    {{ __('logs::logs.referer') }}: {{ $log->referer }}<br>
                    <div class="small text-muted fst-italic mt-2">
                        {{ $log->brow }}, {{ $log->ip }}
                    </div>
                </div>
            </div>
        @endforeach

        {{ $logs->links() }}

        <form action="{{ route('admin.logs.clear') }}" method="post" onsubmit="return confirm('{{ __('logs::logs.confirm_clear') }}')">
            @csrf
            <button type="submit" class="btn btn-danger"><i class="fa fa-trash-alt"></i> {{ __('main.clear') }}</button>
        </form><br>
    @else
        {{ showError(__('logs::logs.empty_logs')) }}
    @endif
@stop
