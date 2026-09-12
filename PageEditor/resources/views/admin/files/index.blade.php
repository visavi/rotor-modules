@extends('layout')

@section('title', $path ?: __('page_editor::files.page_editor'))

@section('header')
    <div class="float-end">
        <a class="btn btn-success" href="{{ route('admin.files.create', ['root' => $root, 'path' => $path]) }}">{{ __('main.create') }}</a>
    </div>

    <h1>{{ $path ?: __('page_editor::files.page_editor') }}</h1>
@stop

@section('breadcrumb')
    @include('page_editor::admin/files/_breadcrumb')
@stop

@section('content')
    @include('page_editor::admin/files/_nav', ['active' => 'files'])

    @if ($searchRoot)
        <form class="mb-3" method="get" action="{{ route('admin.files.search') }}">
            <input type="hidden" name="root" value="{{ $searchRoot }}">
            <div class="input-group">
                <input class="form-control" name="query" value="" placeholder="{{ __('page_editor::files.search_hint') }}">
                <button class="btn btn-outline-secondary">
                    <i class="fas fa-search"></i> {{ __('page_editor::files.search') }}
                </button>
            </div>
        </form>
    @endif

    @if ($entries)
        <ul class="list-group">
            @foreach ($entries as $entry)
                <li class="list-group-item">
                    <div class="float-end">
                        {{-- Каталог модуля удаляется и переименовывается только из админки модулей --}}
                        @unless ($entry['module'])
                            @unless ($entry['dir'])
                                <a class="btn btn-link p-0 me-2" href="{{ route('admin.files.download', ['root' => $root, 'path' => $path, 'file' => $entry['name']]) }}"><i class="fa fa-download"></i></a>
                            @endunless

                            <form action="{{ route('admin.files.rename') }}" method="post" class="d-inline">
                                @csrf
                                <input type="hidden" name="root" value="{{ $root }}">
                                <input type="hidden" name="path" value="{{ $path }}">
                                <input type="hidden" name="filename" value="{{ $entry['name'] }}">
                                <input type="hidden" name="newname" value="">
                                <button class="btn btn-link p-0 me-2" type="button" onclick="return promptAction(this)"
                                        data-prompt="{{ __('page_editor::files.file_name') }}"
                                        data-value="{{ $entry['name'] }}"
                                        data-field="newname"><i class="fa fa-pen"></i></button>
                            </form>

                            <form action="{{ route('admin.files.delete') }}" method="post" class="d-inline"
                                  onsubmit="return confirmAction(this)"
                                  data-confirm="{{ $entry['dir'] ? __('page_editor::files.confirm_delete_dir') : __('page_editor::files.confirm_delete_file') }}">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="root" value="{{ $root }}">
                                <input type="hidden" name="path" value="{{ $path }}">
                                <input type="hidden" name="{{ $entry['dir'] ? 'dirname' : 'filename' }}" value="{{ $entry['name'] }}">
                                <button class="btn btn-link p-0"><i class="fa fa-times"></i></button>
                            </form>
                        @endunless
                    </div>

                    @if ($entry['dir'])
                        <i class="fa fa-folder"></i>
                        <b><a href="{{ route('admin.files.index', ['root' => $root, 'path' => trim($path . '/' . $entry['name'], '/')]) }}">{{ $entry['name'] }}</a></b>
                        @if ($entry['module'])
                            <i class="fas fa-power-off ms-1 {{ $entry['disabled'] ? 'text-muted' : 'text-success' }}"
                               title="{{ $entry['disabled'] ? __('page_editor::files.module_disabled') : __('page_editor::files.module_enabled') }}"></i>
                        @endif
                        <br>
                        {{ __('page_editor::files.objects') }}: {{ $entry['size'] }}
                    @else
                        <i class="fa fa-file"></i>
                        @if ($entry['editable'])
                            <b><a href="{{ route('admin.files.edit', ['root' => $root, 'path' => $path, 'file' => $entry['name']]) }}">{{ $entry['name'] }}</a></b>
                            @if ($entry['overridden'])
                                <i class="fas fa-code-branch text-warning ms-1" title="{{ __('page_editor::files.override_exists') }}"></i>
                            @elseif ($entry['orphan'])
                                <i class="fas fa-unlink text-warning ms-1" title="{{ __('page_editor::files.override_orphan') }}"></i>
                            @endif
                        @else
                            <b>{{ $entry['name'] }}</b>
                        @endif
                        ({{ formatSize($entry['size']) }})<br>
                        @if ($entry['editable'])
                            {{ __('page_editor::files.lines') }}: {{ $entry['lines'] }} /
                        @endif
                        {{ __('page_editor::files.changed') }}: {{ dateFixed(\Illuminate\Support\Carbon::createFromTimestamp($entry['mtime'])) }}
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        {{ showError(__('page_editor::files.empty_objects')) }}
    @endif
@stop
