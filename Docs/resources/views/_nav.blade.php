<div class="docs-search mb-2">
    <form action="/docs/find" method="get">
        <input name="query" class="form-control form-control-sm" type="search"
               placeholder="Поиск по документации..." value="{{ is_string(request('query')) ? request('query') : '' }}" minlength="3" maxlength="64">
    </form>
</div>

@foreach ($menu['rotor']['nav'] as $group)
    <div class="docs-nav-group">
        <div class="docs-nav-group-title">
            <span>{{ $group['title'] }}</span> <span class="badge bg-primary">{{ ROTOR_VERSION }}</span>
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
        <span>Laravel</span> <span class="badge bg-danger">{{ \Modules\Docs\Services\DocsService::LARAVEL_VERSION }}</span>
    </div>
    {{-- Открыта одна группа: name объединяет <details> в группу --}}
    @foreach ($menu['laravel']['nav'] as $group)
        @php
            $hasActive = ($section ?? null) === 'laravel'
                && collect($group['items'])->contains('page', $menu['laravel']['page'] ?? null);
        @endphp
        <details class="docs-nav-group" name="docs-laravel" @if ($hasActive) open @endif>
            <summary class="docs-nav-section-toggle">{{ $group['title'] }}</summary>
            @foreach ($group['items'] as $item)
                @php $isActive = ($section ?? null) === 'laravel' && ($menu['laravel']['page'] ?? null) === $item['page']; @endphp
                <a class="docs-nav-item{{ $isActive ? ' active' : '' }}" href="{{ $item['href'] }}">{{ $item['title'] }}</a>
            @endforeach
        </details>
    @endforeach
@else
    <small class="text-muted">Laravel docs не загружены.<br><code>php artisan docs:sync</code></small>
@endif
