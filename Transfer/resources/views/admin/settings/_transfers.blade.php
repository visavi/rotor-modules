@extends('layout')

@section('title', __('transfer::transfers.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'Transfer']) }}">{{ __('transfer::transfers.cash_transactions') }}</a></li>
            <li class="breadcrumb-item active">{{ __('transfer::transfers.settings') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('transfer::transfers.settings') }}</h1>
@stop

@section('content')
<form method="post" action="{{ route('transfer.settings.update') }}">
    @csrf
    <div class="mb-3{{ hasError('sets[sendmoneypoint]') }}">
        <label for="sendmoneypoint" class="form-label">{{ __('transfer::transfers.points_transfer') }}:</label>
        <input type="number" class="form-control" id="sendmoneypoint" name="sets[sendmoneypoint]" maxlength="4" value="{{ getInput('sets.sendmoneypoint', $settings['sendmoneypoint']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[sendmoneypoint]') }}</div>
    </div>

    <div class="mb-3{{ hasError('sets[listtransfers]') }}">
        <label for="listtransfers" class="form-label">{{ __('transfer::transfers.transfers_per_page') }}:</label>
        <input type="number" class="form-control" id="listtransfers" name="sets[listtransfers]" maxlength="2" value="{{ getInput('sets.listtransfers', $settings['listtransfers']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[listtransfers]') }}</div>
    </div>

    <button class="btn btn-primary">{{ __('main.save') }}</button>
</form>
@stop
