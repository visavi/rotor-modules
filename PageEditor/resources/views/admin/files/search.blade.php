@php use Modules\PageEditor\Support\CodeSearcher; @endphp
@extends('layout')

@section('title', __('page_editor::files.search'))

@section('breadcrumb')
    @include('page_editor::admin/files/_breadcrumb', ['root' => null, 'path' => '', 'active' => __('page_editor::files.search')])
@stop

@section('content')
    <ul class="nav nav-tabs mb-3">
        @foreach ($roots as $item)
            <li class="nav-item">
                <a class="nav-link{{ $item === $root ? ' active' : '' }}"
                   href="{{ route('admin.files.search', ['root' => $item, 'query' => $query, 'mask' => $mask, 'case' => $case ? 1 : null, 'regex' => $regex ? 1 : null]) }}">{{ Lang::has($key = 'page_editor::files.roots.' . $item) ? __($key) : $item }}</a>
            </li>
        @endforeach
    </ul>

    <div class="section-form mb-3 shadow">
        <form method="get" action="{{ route('admin.files.search') }}">
            <input type="hidden" name="root" value="{{ $root }}">
            <div class="input-group">
                <input class="form-control" name="query" value="{{ $query }}" placeholder="{{ __('page_editor::files.search_hint') }}" autofocus>
                <button class="btn btn-primary">{{ __('main.search') }}</button>
            </div>

            {{-- Маска и режимы нужны редко, поэтому раскрываются по требованию --}}
            <details class="mt-2"@if ($mask !== '*' || $case || $regex) open @endif>
                <summary class="small text-muted">{{ __('page_editor::files.search_options') }}</summary>

                <div class="mt-2">
                    <label class="form-label small" for="mask">{{ __('page_editor::files.mask') }}</label>
                    <input class="form-control form-control-sm" name="mask" id="mask" value="{{ $mask }}" placeholder="*.blade.php">
                </div>

                <div class="mt-2">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="case" value="1" id="case" @checked($case)>
                        <label class="form-check-label" for="case">{{ __('page_editor::files.case_sensitive') }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="regex" value="1" id="regex" @checked($regex)>
                        <label class="form-check-label" for="regex">{{ __('page_editor::files.regex') }}</label>
                    </div>
                </div>
            </details>
        </form>
    </div>

    @if ($truncated)
        <div class="alert alert-warning">{{ __('page_editor::files.search_truncated', ['count' => count($results)]) }}</div>
    @endif

    @if ($invalid)
        {{ showError(__('page_editor::files.regex_invalid')) }}
    @elseif ($results)
        <ul class="list-group">
            @foreach ($results as $result)
                @php($dir = dirname($result['file']) === '.' ? '' : dirname($result['file']))
                <li class="list-group-item">
                    <a href="{{ route('admin.files.edit', ['root' => $root, 'path' => $dir, 'file' => basename($result['file']), 'line' => $result['line']]) }}">
                        {{ $result['file'] }}:{{ $result['line'] }}
                    </a>
                    <div class="font-monospace small text-muted text-truncate">{!! CodeSearcher::highlight($result['text'], $query, $case, $regex) !!}</div>
                </li>
            @endforeach
        </ul>
    @elseif ($query !== '')
        {{ showError(__('page_editor::files.search_empty')) }}

        {{-- Один запрос ищется в одном корне, поэтому предлагаем соседние --}}
        @if ($query !== '')
            <div class="mb-3">
                {{ __('page_editor::files.search_other_roots') }}:
                @foreach ($roots as $item)
                    @continue($item === $root)
                    <a class="ms-1" href="{{ route('admin.files.search', ['root' => $item, 'query' => $query, 'mask' => $mask, 'case' => $case ? 1 : null, 'regex' => $regex ? 1 : null]) }}">{{ Lang::has($key = 'page_editor::files.roots.' . $item) ? __($key) : $item }}</a>
                @endforeach
            </div>
        @endif
    @endif
@stop
