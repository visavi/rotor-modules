{{-- Общие хлебные крошки редактора: корень и каталоги остаются кликабельными --}}
@php
    $segments = ($path ?? '') === '' ? [] : explode('/', $path);
    $active = $active ?? null;
    $root = $root ?? null;
@endphp

<nav>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.files.index') }}">{{ __('page_editor::files.page_editor') }}</a></li>

        @if ($root)
            @php $label = Lang::has($key = 'page_editor::files.roots.' . $root) ? __($key) : $root; @endphp

            @if ($segments || $active !== null)
                <li class="breadcrumb-item"><a href="{{ route('admin.files.index', ['root' => $root]) }}">{{ $label }}</a></li>
            @else
                <li class="breadcrumb-item active">{{ $label }}</li>
            @endif
        @endif

        @foreach ($segments as $index => $segment)
            @php $sub = implode('/', array_slice($segments, 0, $index + 1)); @endphp

            @if ($active === null && $index === count($segments) - 1)
                <li class="breadcrumb-item active">{{ $segment }}</li>
            @else
                <li class="breadcrumb-item"><a href="{{ route('admin.files.index', ['root' => $root, 'path' => $sub]) }}">{{ $segment }}</a></li>
            @endif
        @endforeach

        @if ($active !== null)
            <li class="breadcrumb-item active">{{ $active }}</li>
        @endif
    </ol>
</nav>
