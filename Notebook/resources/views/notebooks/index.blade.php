@extends('layout')

@section('title', __('notebook::notebooks.notebook'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/menu">{{ __('main.menu') }}</a></li>
            <li class="breadcrumb-item active">{{ __('notebook::notebooks.notebook') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="mb-3">
        {{ __('notebook::notebooks.info') }}
    </div>

    @if ($note->text)
        <div class="mb-3">
            {{ __('notebook::notebooks.subtitle') }}:<br>
            {{ $note->getText() }}
        </div>

        <p class="text-muted fst-italic">
            {{ __('notebook::notebooks.last_edited') }}: {{ dateFixed($note->updated_at) }}
        </p>
    @else
        {{ showError(__('notebook::notebooks.empty_note')) }}
    @endif

    <i class="fa fa-pencil-alt"></i> <a href="{{ route('notebooks.edit') }}">{{ __('main.edit') }}</a><br>
@stop
