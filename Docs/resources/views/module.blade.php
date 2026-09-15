@extends('layout')

@section('title', $info['name'] ?? $name)

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/rotor">RotorCMS</a></li>
            <li class="breadcrumb-item"><a href="/rotor/modules">{{ __('docs::rotor.page_modules') }}</a></li>
            <li class="breadcrumb-item active">{{ $info['name'] ?? $name }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @include('docs::_module_card', ['name' => $name, 'info' => $info, 'single' => true])

    @if (! empty($info['changelog']))
        <div class="section mb-3 shadow">
            <div class="section-title">{{ __('docs::rotor.changelog') }}</div>
            <div class="section-content markdown-body">{{ renderMarkdown($info['changelog']) }}</div>
        </div>
    @endif

    <a href="/rotor/modules" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>{{ __('docs::rotor.page_modules') }}
    </a>
@stop
