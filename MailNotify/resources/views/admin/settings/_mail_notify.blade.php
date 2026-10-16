@extends('admin/settings/layout')

@section('title', __('mail_notify::mail_notify.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'mail-notify']) }}">{{ __('admin.modules.module') }} {{ __('mail_notify::mail_notify.title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('mail_notify::mail_notify.settings') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('mail_notify::mail_notify.settings') }}</h1>
@stop

@section('settings')
    <form method="post" action="{{ route('mail-notify.settings.update') }}">
        @csrf
        <div class="mb-3{{ hasError('sets[sendprivatmailday]') }}">
            <label for="sendprivatmailday" class="form-label">{{ __('mail_notify::mail_notify.count_days') }}:</label>
            <input type="number" class="form-control" id="sendprivatmailday" name="sets[sendprivatmailday]" min="1" max="99" value="{{ old('sets.sendprivatmailday', $settings['sendprivatmailday'] ?? 3) }}" required>
            <div class="invalid-feedback">{{ textError('sets[sendprivatmailday]') }}</div>
            <div class="form-text">{{ __('mail_notify::mail_notify.count_days_help') }}</div>
        </div>

        <div class="mb-3{{ hasError('sets[sendmailpacket]') }}">
            <label for="sendmailpacket" class="form-label">{{ __('mail_notify::mail_notify.packet') }}:</label>
            <input type="number" class="form-control" id="sendmailpacket" name="sets[sendmailpacket]" min="1" max="999" value="{{ old('sets.sendmailpacket', $settings['sendmailpacket'] ?? 3) }}" required>
            <div class="invalid-feedback">{{ textError('sets[sendmailpacket]') }}</div>
            <div class="form-text">{{ __('mail_notify::mail_notify.packet_help') }}</div>
        </div>

        <button class="btn btn-primary">{{ __('main.save') }}</button>
    </form>
@stop
