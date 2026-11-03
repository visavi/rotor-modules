@extends('layout')

@section('title', __('privacy::privacy.title'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('privacy::privacy.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section mb-3 shadow">
        <div class="section-body section-message">{{ renderHtml($version->text) }}</div>
    </div>

    <div class="text-muted fst-italic mb-3">
        {{ __('privacy::privacy.version', ['id' => $version->id, 'date' => dateFixed($version->created_at, 'd.m.Y', true)]) }}
    </div>
@stop
