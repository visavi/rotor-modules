@extends('layout')

@section('title', __('page_editor::files.create_object'))

@section('breadcrumb')
    @include('page_editor::admin/files/_breadcrumb', ['active' => __('page_editor::files.create_object')])
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="section-form mb-3 shadow">
                    <form action="{{ route('admin.files.create', ['root' => $root, 'path' => $path]) }}" method="post">
                        @csrf
                        <div class="mb-3{{ hasError('dirname') }}">
                            <label for="dirname" class="form-label">{{ __('page_editor::files.directory_name') }}:</label>
                            <input type="text" class="form-control" id="dirname" name="dirname" maxlength="255" value="{{ old('dirname') }}" required>
                            <div class="invalid-feedback">{{ textError('dirname') }}</div>
                        </div>

                        <button class="btn btn-primary">{{ __('page_editor::files.create_directory') }}</button>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="section-form mb-3 shadow">
                    <form action="{{ route('admin.files.create', ['root' => $root, 'path' => $path]) }}" method="post">
                        @csrf
                        <div class="mb-3{{ hasError('filename') }}">
                            <label for="filename" class="form-label">{{ __('page_editor::files.file_name') }}:</label>
                            <input type="text" class="form-control" id="filename" name="filename" maxlength="255" value="{{ old('filename') }}" required>
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
