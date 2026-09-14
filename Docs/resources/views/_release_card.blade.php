@php
    $latest ??= false;
    $single ??= false;
    $tag = $release['tag_name'];

    $assets = collect($release['assets'] ?? []);
    $upgrade = $assets->first(fn ($a) => str_contains($a['name'], 'upgrade'));
    $full = $assets->first(fn ($a) => ! str_contains($a['name'], 'upgrade'));
    $downloads = (int) $assets->sum('download_count');
@endphp

<article class="rel-card{{ $latest ? ' rel-card--latest' : '' }}{{ $single ? ' rel-card--single' : '' }}" id="release-{{ $tag }}">
    <div class="rel-card__aside">
        <span class="rel-tag">{{ $tag }}</span>
        @if ($latest)
            <span class="rel-flag rel-flag--latest">{{ __('docs::rotor.latest_badge') }}</span>
        @endif
        @if ($release['prerelease'] ?? false)
            <span class="rel-flag rel-flag--pre">{{ __('docs::rotor.prerelease') }}</span>
        @endif
    </div>

    <div class="rel-card__body">
        <h2 class="rel-card__title">
            @if ($single)
                {{ $release['name'] ?: $tag }}
            @else
                <a href="/rotor/releases/{{ rawurlencode($tag) }}">{{ $release['name'] ?: $tag }}</a>
            @endif
        </h2>

        <div class="rel-meta">
            <img class="rel-meta__avatar rounded-circle" src="{{ $release['author']['avatar_url'] }}" alt="{{ $release['author']['login'] }}">
            <a class="rel-meta__author" href="{{ $release['author']['html_url'] }}">{{ $release['author']['login'] }}</a>
            <span class="rel-meta__sep">&middot;</span>
            <span class="rel-meta__date">{{ dateFixed(\Illuminate\Support\Facades\Date::parse($release['created_at'])) }}</span>
            @if (! $single)
                <span class="rel-meta__sep">&middot;</span>
                <a class="rel-meta__link" href="/rotor/releases/{{ rawurlencode($tag) }}" title="{{ __('docs::rotor.permalink') }}">
                    <i class="fas fa-link"></i>
                </a>
            @endif
        </div>

        @if ($release['body'])
            @if ($single)
                <div class="rel-body markdown-body">{{ renderMarkdown($release['body']) }}</div>
            @else
                <details class="rel-spoiler spoiler">
                    <summary>{{ __('docs::rotor.description') }}</summary>
                    <div class="rel-spoiler__inner markdown-body">{{ renderMarkdown($release['body']) }}</div>
                </details>
            @endif
        @endif

        <div class="rel-actions">
            <a class="btn btn-sm btn-outline-secondary" href="{{ $release['html_url'] }}" rel="noopener" target="_blank">
                <i class="fab fa-github me-1"></i>{{ __('docs::rotor.release_page') }}
            </a>

            @if ($full)
                <a class="btn btn-sm btn-primary" href="{{ $full['browser_download_url'] }}">
                    <i class="fas fa-download me-1"></i>{{ __('docs::rotor.download_full') }}
                    <span class="rel-asset__size">{{ formatSize($full['size']) }}</span>
                </a>
            @endif

            @if ($upgrade)
                <a class="btn btn-sm btn-outline-primary" href="{{ $upgrade['browser_download_url'] }}">
                    <i class="fas fa-rotate me-1"></i>{{ __('docs::rotor.download_upgrade') }}
                    <span class="rel-asset__size">{{ formatSize($upgrade['size']) }}</span>
                </a>
            @endif

            @if ($downloads > 0)
                <span class="rel-asset__count">
                    <i class="fas fa-arrow-down-long me-1"></i>{{ $downloads }} {{ __('docs::rotor.downloads') }}
                </span>
            @endif
        </div>
    </div>
</article>
