@extends('layout')

@section('title', __('page_editor::files.create_object'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="/admin/files">{{ __('page_editor::files.page_editor') }}</a></li>
            @if ($path)
                <li class="breadcrumb-item"><a href="/admin/files?path={{ $path }}">{{ $path }}</a></li>
            @endif
            <li class="breadcrumb-item active">{{ __('page_editor::files.create_object') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="section-form mb-3 shadow">
                    <form action="/admin/files/create?path={{ $path }}" method="post">
                        @csrf
                        <div class="mb-3{{ hasError('dirname') }}">
                            <label for="dirname" class="form-label">{{ __('page_editor::files.directory_name') }}:</label>
                            <input type="text" class="form-control" id="dirname" name="dirname" maxlength="30" value="{{ getInput('dirname') }}" required>
                            <div class="invalid-feedback">{{ textError('dirname') }}</div>
                        </div>

                        <button class="btn btn-primary">{{ __('page_editor::files.create_directory') }}</button>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="section-form mb-3 shadow">
                    <form action="/admin/files/create?path={{ $path }}" method="post">
                        @csrf
                        <div class="mb-3{{ hasError('filename') }}">
                            <label for="filename" class="form-label">{{ __('page_editor::files.file_name') }}:</label>
                            <input type="text" class="form-control" id="filename" name="filename" maxlength="30" value="{{ getInput('filename') }}" required>
                            <div class="invalid-feedback">{{ textError('filename') }}</div>
                        </div>

                        <button class="btn btn-primary">{{ __('page_editor::files.create_file') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <p class="text-muted fst-italic">{{ __('page_editor::files.create_hint') }}</p>
    </div>
@stop
