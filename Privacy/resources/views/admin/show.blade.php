@extends('layout')

@section('title', __('privacy::privacy.version', ['id' => $version->id, 'date' => dateFixed($version->created_at, 'd.m.Y', true)]))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('privacy.admin.index') }}">{{ __('privacy::privacy.admin_title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('privacy::privacy.version', ['id' => $version->id, 'date' => dateFixed($version->created_at, 'd.m.Y', true)]) }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section mb-3 shadow">
        <div class="section-body section-message">{{ renderHtml($version->text) }}</div>
    </div>

    <div class="text-muted fst-italic mb-3">
        {{ dateFixed($version->created_at, 'd.m.Y / H:i', true) }}, {{ __('privacy::privacy.consents_count') }}: {{ $version->consents_count }}
    </div>
@stop
