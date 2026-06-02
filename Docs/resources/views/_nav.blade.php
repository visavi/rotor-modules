<div class="docs-search mb-2">
    <form action="/docs/search" method="get">
        <input name="q" class="form-control form-control-sm" type="search"
               placeholder="Поиск по документации..." value="{{ request('q') }}" minlength="3" maxlength="64">
    </form>
</div>

@foreach ($menu['rotor']['nav'] as $group)
    <div class="docs-nav-group">
        <div class="docs-nav-group-title">
            {{ $group['title'] }} <span class="docs-badge bg-primary">{{ ROTOR_VERSION }}</span>
        </div>
        @foreach ($group['items'] as $item)
            @php $isActive = ($section ?? null) === 'rotor' && ($menu['rotor']['page'] ?? null) === $item['page']; @endphp
            <a class="docs-nav-item{{ $isActive ? ' active' : '' }}" href="{{ $item['href'] }}">{{ $item['title'] }}</a>
        @endforeach
    </div>
@endforeach

<div class="docs-divider"></div>

@if (!empty($menu['laravel']['nav']))
    <div class="docs-nav-group-title mb-1">
        Laravel <span class="docs-badge">{{ \Modules\Docs\Services\DocsService::LARAVEL_VERSION }}</span>
    </div>
    <div id="docs-laravel-accordion">
    @foreach ($menu['laravel']['nav'] as $group)
        @php
            $groupId = 'lg-' . $loop->index;
            $hasActive = ($section ?? null) === 'laravel'
                && collect($group['items'])->contains('page', $menu['laravel']['page'] ?? null);
        @endphp
        <div class="docs-nav-group">
            <button class="docs-nav-section-toggle{{ $hasActive ? '' : ' collapsed' }}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#{{ $groupId }}"
                    aria-expanded="{{ $hasActive ? 'true' : 'false' }}">
                {{ $group['title'] }}
            </button>
            <div class="collapse{{ $hasActive ? ' show' : '' }}" id="{{ $groupId }}">
                @foreach ($group['items'] as $item)
                    @php $isActive = ($section ?? null) === 'laravel' && ($menu['laravel']['page'] ?? null) === $item['page']; @endphp
                    <a class="docs-nav-item{{ $isActive ? ' active' : '' }}" href="{{ $item['href'] }}">{{ $item['title'] }}</a>
                @endforeach
            </div>
        </div>
    @endforeach
    </div>
@else
    <small class="text-muted">Laravel docs не загружены.<br><code>php artisan docs:sync</code></small>
@endif
