{{-- Общая навигация редактора: корни файлов и вкладка переводов --}}
<ul class="nav nav-tabs mb-3">
    @foreach ($roots as $item)
        <li class="nav-item">
            <a class="nav-link{{ $active === 'files' && $item === $root ? ' active' : '' }}"
               href="{{ route('admin.files.index', ['root' => $item]) }}">{{ Lang::has($key = 'page_editor::files.roots.' . $item) ? __($key) : $item }}</a>
        </li>
    @endforeach

    <li class="nav-item ms-auto">
        <a class="nav-link{{ $active === 'translations' ? ' active' : '' }}" href="{{ route('admin.files.translations') }}">
            <i class="fas fa-language"></i> {{ __('page_editor::files.translations') }}
        </a>
    </li>
</ul>
