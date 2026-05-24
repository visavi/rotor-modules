@extends('layout')

@section('title', __('backup::backup.backup'))

@section('header')
    <div class="float-end">
        <a class="btn btn-success" href="/admin/backups/create">{{ __('backup::backup.create_backup') }}</a><br>
    </div>

    <h1>{{ __('backup::backup.backup') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('backup::backup.backup') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($files)
        @foreach ($files as $file)
            <div class="section mb-3 shadow">
                <i class="fa fa-archive"></i> <b>{{ basename($file) }}</b> ({{ formatFileSize($file) }})

                <div class="float-end">
                    <form action="/admin/backups/delete" method="post" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="file" value="{{ basename($file) }}">
                        <button class="btn btn-link p-0"><i class="fas fa-times"></i></button>
                    </form>
                </div>
            </div>
        @endforeach

        {{ __('backup::backup.total_backups') }}: <b>{{ count($files) }}</b><br>
    @else
        {{ showError(__('backup::backup.empty_backups')) }}
    @endif
@stop
