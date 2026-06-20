@extends('layout')

@section('title', __('docs::rotor.page_modules'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/rotor">RotorCMS</a></li>
            <li class="breadcrumb-item active">{{ __('docs::rotor.page_modules') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($modules)
        <div class="row g-2 mb-3">
            <div class="col-md-8">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" placeholder="{{ __('main.search') }}" autocomplete="off" data-module-search>
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select form-select-sm" data-module-sort>
                    <option value="name">{{ __('main.sort') }}: {{ __('main.title') }}</option>
                    <option value="version">{{ __('main.sort') }}: {{ __('main.version') }}</option>
                </select>
            </div>
        </div>

        <div data-module-list>
        @foreach ($modules as $name => $info)
            @php
                $searchText = mb_strtolower(trim(($info['name'] ?? $name) . ' ' . $name . ' ' . ($info['description'] ?? '') . ' ' . ($info['author'] ?? '')));
                $versions   = $info['versions'] ?? [];
            @endphp
            <div class="section mb-3 shadow" data-module-card
                 data-search="{{ $searchText }}"
                 data-name="{{ mb_strtolower($info['name'] ?? $name) }}"
                 data-version="{{ $info['version'] ?? '0' }}">
                <div class="section-title d-flex align-items-center justify-content-between gap-2">
                    <div class="text-break" style="min-width: 0">
                        <i class="fas fa-cube text-muted"></i>
                        <span class="fw-bold">{{ $info['name'] ?? $name }}</span>
                        <small class="text-muted">({{ $name }})</small>
                    </div>
                    @if (! empty($info['download_url']))
                        <a class="btn btn-sm btn-primary text-nowrap flex-shrink-0" href="{{ $info['download_url'] }}">
                            <i class="fas fa-download me-1"></i>{{ __('main.download') }}
                            <span class="opacity-75">{{ $info['version'] ?? '' }}</span>
                        </a>
                    @endif
                </div>

                <div class="section-content">
                    @if (! empty($info['description']))
                        <p class="mb-2">{{ $info['description'] }}</p>
                    @endif

                    <div class="small text-muted">
                        {{ __('main.version') }}: <span class="text-body">{{ $info['version'] ?? '—' }}</span>
                        <span class="mx-1">&middot;</span>
                        {{ __('main.author') }}: <span class="text-body">{{ $info['author'] ?? '—' }}</span>
                        @if (! empty($info['requires']))
                            <span class="mx-1">&middot;</span>
                            {{ __('docs::rotor.requires') }}: Rotor &ge; {{ $info['requires'] }}
                        @endif
                    </div>

                    @if (! empty($info['homepage']))
                        <div class="small mt-1">
                            <i class="fas fa-link text-muted me-1"></i><a href="{{ $info['homepage'] }}" rel="noopener" target="_blank">{{ $info['homepage'] }}</a>
                        </div>
                    @endif

                    @if (count($versions) > 1)
                        <details class="mt-2">
                            <summary class="text-primary small" style="cursor: pointer">{{ __('main.version') }} ({{ count($versions) }})</summary>
                            <ul class="list-unstyled mb-0 mt-2 small">
                                @foreach ($versions as $ver)
                                    <li class="d-flex align-items-center flex-wrap gap-2 py-1 border-top">
                                        <span class="badge bg-adaptive">{{ $ver['version'] ?? '—' }}</span>
                                        @if (! empty($ver['requires']))
                                            <span class="text-muted">{{ __('docs::rotor.requires') }}: Rotor &ge; {{ $ver['requires'] }}</span>
                                        @endif
                                        @if (! empty($ver['download_url']))
                                            <a class="btn btn-sm btn-outline-primary ms-auto" href="{{ $ver['download_url'] }}">
                                                <i class="fas fa-download me-1"></i>{{ __('main.download') }}
                                            </a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif

                    @if (! empty($info['conflict']))
                        <div class="mt-2">
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-exclamation-triangle"></i> {{ implode(', ', $info['conflict']) }}
                            </span>
                        </div>
                    @endif

                    <div class="small text-muted mt-2">{{ __('docs::rotor.source') }}: {{ $info['registry'] }}</div>
                </div>
            </div>
        @endforeach
        </div>

        <div class="d-none" data-module-empty>
            {{ showError(__('main.nothing_found')) }}
        </div>

        @push('scripts')
            <script>
                (function () {
                    const list = document.querySelector('[data-module-list]');
                    if (! list) {
                        return;
                    }

                    const empty  = document.querySelector('[data-module-empty]');
                    const search = document.querySelector('[data-module-search]');
                    const sort   = document.querySelector('[data-module-sort]');
                    const cards  = Array.from(list.querySelectorAll('[data-module-card]'));

                    function applyFilters() {
                        const query = search ? search.value.trim().toLowerCase() : '';
                        let visible = 0;

                        cards.forEach(card => {
                            const show = query === '' || (card.dataset.search || '').includes(query);
                            card.classList.toggle('d-none', !show);
                            if (show) visible++;
                        });

                        if (empty) {
                            empty.classList.toggle('d-none', visible > 0);
                        }
                    }

                    function applySort() {
                        if (! sort) {
                            return;
                        }

                        const mode = sort.value;
                        const sorted = cards.slice().sort((a, b) => {
                            if (mode === 'version') {
                                return (b.dataset.version || '').localeCompare(a.dataset.version || '', undefined, { numeric: true });
                            }
                            return (a.dataset.name || '').localeCompare(b.dataset.name || '');
                        });

                        sorted.forEach(card => list.appendChild(card));
                    }

                    if (search) {
                        search.addEventListener('input', applyFilters);
                    }

                    if (sort) {
                        sort.addEventListener('change', applySort);
                    }

                    applySort();
                    applyFilters();
                })();
            </script>
        @endpush
    @else
        {{ showError(__('docs::rotor.modules_error')) }}
    @endif
@stop
