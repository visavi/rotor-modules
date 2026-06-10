@if (view()->exists('theme::sidebar'))
    @section('sidebar')
        <div class="app-sidebar__overlay" data-bs-toggle="sidebar"></div>
        <aside class="app-sidebar docs-in-sidebar">
            @include('docs::_nav')
        </aside>
    @endsection
@endif

@once
    @push('styles')
        <style>
            /* Навигация документации в слоте sidebar темы.
               Цвет текста наследуется от сайдбара темы (currentColor): тёмный сайдбар → светлый текст,
               светлый → тёмный. Фон не навязываем — берём фон сайдбара темы. */
            .docs-in-sidebar { padding-left: .75rem; padding-right: .75rem; }
            .docs-in-sidebar a { text-decoration: none; }
            .docs-in-sidebar .docs-search { margin-top: .75rem; margin-bottom: 1rem; }
            .docs-in-sidebar .docs-nav-item,
            .docs-in-sidebar .docs-nav-section-toggle { color: inherit; }
            .docs-in-sidebar .docs-nav-group-title { color: inherit; }
            .docs-in-sidebar .docs-nav-group-title > span:first-child { opacity: .6; }
            .docs-in-sidebar .docs-divider { border-color: currentColor; opacity: .15; }
            /* Ховер с гарантированным контрастом на любом фоне */
            .docs-in-sidebar .docs-nav-item:hover,
            .docs-in-sidebar .docs-nav-section-toggle:hover { background: rgba(128, 128, 128, .18); color: inherit; }

            /* Стили навигации */
            .docs-nav-group { margin-bottom: .5rem; }
            .docs-nav-group-title { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--bs-secondary-color); padding: .25rem .5rem; margin-bottom: .25rem; }
            .docs-nav-section-toggle { display: flex; justify-content: space-between; align-items: center; width: 100%; font-size: .875rem; font-weight: 600; padding: .3rem .5rem; margin-top: .25rem; color: var(--bs-body-color); background: none; border: none; border-radius: .375rem; text-align: left; cursor: pointer; }
            .docs-nav-section-toggle:hover { background: var(--bs-tertiary-bg); }
            .docs-nav-section-toggle::after { content: ''; display: inline-block; width: .4rem; height: .4rem; border-right: 1.5px solid currentColor; border-bottom: 1.5px solid currentColor; transform: rotate(225deg); transition: transform .2s; flex-shrink: 0; margin-left: .4rem; opacity: .5; }
            .docs-nav-section-toggle.collapsed::after { transform: rotate(45deg); }
            .docs-nav-item { display: block; padding: .3rem .5rem; border-radius: .375rem; font-size: .875rem; color: var(--bs-body-color); text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .docs-nav-item:hover { background: var(--bs-tertiary-bg); color: var(--bs-body-color); }
            .docs-nav-item.active { background: var(--bs-primary); color: #fff; }
            .docs-divider { border-top: 1px solid var(--bs-border-color); margin: 1rem 0; }
        </style>
    @endpush

    @push('scripts')
        <script>
            var acc = document.getElementById('docs-laravel-accordion');
            if (acc) {
                acc.addEventListener('show.bs.collapse', function (e) {
                    acc.querySelectorAll('.collapse.show').forEach(function (el) {
                        if (el !== e.target) {
                            var btn = acc.querySelector('[data-bs-target="#' + el.id + '"]');
                            if (btn) btn.click();
                        }
                    });
                });
            }
        </script>
    @endpush
@endonce
