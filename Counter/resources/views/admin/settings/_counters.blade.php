@extends('layout')

@section('title', __('counter::counters.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'Counter']) }}">{{ __('admin.modules.module') }} {{ __('counter::counters.statistics') }}</a></li>
            <li class="breadcrumb-item active">{{ __('counter::counters.settings') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('counter::counters.settings') }}</h1>
@stop

@section('content')
<form method="post" action="{{ route('counter.settings.update') }}">
    @csrf
    <?php
    $counters = [
        __('main.disable'),
        __('counter::counters.hosts_hosts_all'),
        __('counter::counters.hits_hits_all'),
        __('counter::counters.hits_hosts'),
        __('counter::counters.hits_all_hosts_all'),
    ];
    $inputCounter = (int) getInput('sets.incount', $settings['incount']);
    ?>
    <div class="mb-3{{ hasError('sets[incount]') }}">
        <label for="incount" class="form-label">{{ __('counter::counters.counters_enable') }}:</label>
        <select class="form-select" id="incount" name="sets[incount]">

            @foreach ($counters as $key => $counter)
                <option value="{{ $key }}"{{ $key === $inputCounter ? ' selected' : '' }}>{{ $counter }}</option>
            @endforeach

        </select>
        <div class="invalid-feedback">{{ textError('sets[incount]') }}</div>
    </div>

    <button class="btn btn-primary">{{ __('main.save') }}</button>
</form>
@stop
