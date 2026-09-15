@php
    $single      = $single ?? false;
    $versions    = $info['versions'] ?? [];
    $screenshots = array_filter((array) ($info['screenshots'] ?? []));
    $searchText  = mb_strtolower(trim(($info['name'] ?? $name) . ' ' . $name . ' ' . ($info['description'] ?? '') . ' ' . ($info['author'] ?? '')));
@endphp

<div class="section mb-3 shadow" @if (! $single) data-module-card
     data-search="{{ $searchText }}"
     data-name="{{ mb_strtolower($info['name'] ?? $name) }}"
     data-version="{{ $info['version'] ?? '0' }}"
     data-released="{{ $info['released_at'] ?? '' }}" @endif>
    <div class="section-title d-flex align-items-center justify-content-between gap-2">
        <div class="text-break" style="min-width: 0">
            <i class="fas fa-cube text-muted"></i>
            @if ($single)
                <span class="fw-bold">{{ $info['name'] ?? $name }}</span>
            @else
                <a class="fw-bold" href="/rotor/modules/{{ $name }}">{{ $info['name'] ?? $name }}</a>
            @endif
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
            @if (! empty($info['released_at']))
                <span class="mx-1">&middot;</span>
                {{ __('main.date') }}: <span class="text-body">{{ date('d.m.Y', strtotime($info['released_at'])) }}</span>
            @endif
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

        @if ($single && $screenshots)
            <div class="row g-2 mt-2">
                @foreach ($screenshots as $screenshot)
                    <div class="col-6 col-md-4">
                        <a href="{{ $screenshot }}" data-fancybox="module-{{ $name }}">
                            <img src="{{ $screenshot }}" class="img-fluid rounded border" loading="lazy" alt="{{ $info['name'] ?? $name }}">
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        @if (count($versions) > 1)
            <details class="mt-2" @if ($single) open @endif>
                <summary class="text-primary small" style="cursor: pointer">{{ __('main.version') }} ({{ count($versions) }})</summary>
                <ul class="list-unstyled mb-0 mt-2 small">
                    @foreach ($versions as $ver)
                        <li class="d-flex align-items-center flex-wrap gap-2 py-1 border-top">
                            <span class="badge bg-adaptive">{{ $ver['version'] ?? '—' }}</span>
                            @if (! empty($ver['released_at']))
                                <span class="text-muted">{{ date('d.m.Y', strtotime($ver['released_at'])) }}</span>
                            @endif
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
