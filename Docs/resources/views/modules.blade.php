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
                    <option value="released">{{ __('main.sort') }}: {{ __('main.date') }}</option>
                </select>
            </div>
        </div>

        <div data-module-list>
        @foreach ($modules as $name => $info)
            @include('docs::_module_card', ['name' => $name, 'info' => $info])
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
                            if (mode === 'released') {
                                // Без даты (релиз до появления поля) — в конец, там по названию
                                const da = a.dataset.released || '';
                                const db = b.dataset.released || '';
                                if (da === '' || db === '') {
                                    return da === db
                                        ? (a.dataset.name || '').localeCompare(b.dataset.name || '')
                                        : (da === '' ? 1 : -1);
                                }
                                return db.localeCompare(da);
                            }
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
