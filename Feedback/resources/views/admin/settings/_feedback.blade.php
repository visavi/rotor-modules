@extends('admin/settings/layout')

@section('title', __('feedback::feedback.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'feedback']) }}">{{ __('admin.modules.module') }} {{ __('feedback::feedback.title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('feedback::feedback.settings') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('feedback::feedback.settings') }}</h1>
@stop

@section('settings')
    @php($delivery = old('sets.feedback_delivery', $settings['feedback_delivery'] ?? 'messages'))

    <form method="post" action="{{ route('feedback.settings.update') }}">
        @csrf

        <div class="mb-3{{ hasError('sets.feedback_delivery') }}">
            <div class="form-label">{{ __('feedback::feedback.delivery') }}:</div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="sets[feedback_delivery]" id="delivery_messages" value="messages"{{ $delivery === 'messages' ? ' checked' : '' }}>
                <label class="form-check-label" for="delivery_messages">{{ __('feedback::feedback.delivery_messages') }}</label>
                <div class="form-text mt-0">{{ __('feedback::feedback.delivery_messages_help') }}</div>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="sets[feedback_delivery]" id="delivery_email" value="email"{{ $delivery === 'email' ? ' checked' : '' }}>
                <label class="form-check-label" for="delivery_email">{{ __('feedback::feedback.delivery_email') }}</label>
                <div class="form-text mt-0">{{ __('feedback::feedback.delivery_email_help') }}</div>
            </div>

            <div class="invalid-feedback d-block">{{ textError('sets.feedback_delivery') }}</div>
        </div>

        <div class="mb-3{{ hasError('sets.feedback_email') }}">
            <label for="feedback_email" class="form-label">{{ __('feedback::feedback.recipient') }}:</label>
            <input type="email" class="form-control" id="feedback_email" name="sets[feedback_email]" maxlength="100" value="{{ old('sets.feedback_email', $settings['feedback_email'] ?? '') }}" placeholder="{{ config('app.email') }}">
            <div class="invalid-feedback">{{ textError('sets.feedback_email') }}</div>
            <div class="form-text">{{ __('feedback::feedback.recipient_help') }}</div>
        </div>

        <button class="btn btn-primary">{{ __('main.save') }}</button>
    </form>
@stop
