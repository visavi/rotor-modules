@extends('layout')

@section('title', __('page_editor::files.translations'))

@section('breadcrumb')
    @include('page_editor::admin/files/_breadcrumb', ['root' => null, 'path' => '', 'active' => __('page_editor::files.translations')])
@stop

@section('content')
    @include('page_editor::admin/files/_nav', ['active' => 'translations', 'root' => null])

    <form class="mb-3" method="get" action="{{ route('admin.files.translations') }}">
        <div class="input-group">
            <input class="form-control" name="query" value="{{ $query }}" placeholder="{{ __('page_editor::files.translations_search_hint') }}">
            <button class="btn btn-primary">{{ __('main.search') }}</button>
        </div>
    </form>

    @if ($query !== '')
        @if ($found)
            <div class="table-responsive">
            <table class="table table-sm" style="table-layout: fixed">
                <thead>
                    <tr>
                        <th style="width: 24%">{{ __('page_editor::files.key') }}</th>
                        @foreach ($locales as $locale)
                            <th>{{ $locale }}</th>
                        @endforeach
                        <th style="width: 40px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($found as $item)
                        <tr>
                            <td class="font-monospace small" style="word-break: break-word">
                                <a href="{{ route('admin.files.translations', ['group' => $item['namespace'] ? $item['namespace'] . '::' . $item['group'] : $item['group']]) }}">
                                    {{ $item['namespace'] ? $item['namespace'] . '::' : '' }}{{ $item['group'] }}.{{ $item['key'] }}
                                </a>
                            </td>
                            @foreach ($locales as $locale)
                                {{-- Описания бывают длинными: без ограничения таблица уезжает за экран --}}
                                {{-- Пути и ссылки внутри переводов длинные и без пробелов --}}
                                <td class="small" style="word-break: break-word">{{ $item['values'][$locale] ?? '' }}</td>
                            @endforeach
                            <td>
                                {{-- Ищем ключ в том виде, в каком он пишется в коде: <группа>.<ключ> или <модуль>::<группа>.<ключ> --}}
                                @php($usage = ($item['namespace'] ? $item['namespace'] . '::' : '') . $item['group'] . '.' . $item['key'])

                                <a class="btn btn-link p-0" title="{{ __('page_editor::files.where_used') }}" href="{{ route('admin.files.search', ['root' => 'views', 'query' => $usage]) }}">
                                    <i class="fas fa-search"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @else
            {{ showError(__('page_editor::files.search_empty')) }}
        @endif
    @else
        <div class="row">
            <div class="col-md-3">
                <div class="list-group mb-3" style="max-height: 70vh; overflow-y: auto;">
                    @foreach ($groups as $group)
                        <a class="list-group-item list-group-item-action{{ $current && $group['label'] === $current['label'] ? ' active' : '' }}"
                           href="{{ route('admin.files.translations', ['group' => $group['label']]) }}">{{ $group['label'] }}
                            {{-- Значок только у модулей: у групп ядра состояния нет --}}
                            @if ($group['namespace'] !== null)
                                <i class="fas fa-power-off ms-1 {{ $group['enabled'] ? 'text-success' : 'text-muted' }}"
                                   title="{{ $group['enabled'] ? __('page_editor::files.module_enabled') : __('page_editor::files.module_disabled') }}"></i>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="col-md-9">
                @if ($current)
                    <form method="post" action="{{ route('admin.files.translations.save') }}">
                        @csrf
                        <input type="hidden" name="namespace" value="{{ $current['namespace'] }}">
                        <input type="hidden" name="group" value="{{ $current['group'] }}">

                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 18%">{{ __('page_editor::files.key') }}</th>
                                    @foreach ($locales as $locale)
                                        <th>{{ $locale }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lines as $key => $values)
                                    <tr>
                                        <td class="font-monospace small">{{ $key }}</td>
                                        @foreach ($locales as $locale)
                                            @php($overridden = $overrides[$key][$locale] ?? false)
                                            @php($value = $values[$locale] ?? '')

                                            {{-- Высота по длине текста: короткие значения не занимают лишнего,
                                                 длинные видно целиком без внутренней прокрутки --}}
                                            @php($rows = min(8, max(2, (int) ceil(mb_strlen($value) / 18))))

                                            <td>
                                                <textarea class="form-control form-control-sm{{ $overridden ? ' border-warning' : '' }}" name="lines[{{ $key }}][{{ $locale }}]" rows="{{ $rows }}">{{ $value }}</textarea>

                                                @if ($overridden)
                                                    <div class="form-check form-check-inline mt-1">
                                                        <input class="form-check-input" type="checkbox" value="1" id="reset_{{ $loop->parent->index }}_{{ $locale }}" name="reset[{{ $key }}][{{ $locale }}]">
                                                        <label class="form-check-label small text-muted" for="reset_{{ $loop->parent->index }}_{{ $locale }}">{{ __('page_editor::files.reset') }}</label>
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <button class="btn btn-primary">{{ __('main.save') }}</button>
                    </form>
                @endif
            </div>
        </div>
    @endif
@stop
