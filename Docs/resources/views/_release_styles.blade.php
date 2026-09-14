@push('styles')
    <style>
        .rel-feed { display: flex; flex-direction: column; gap: 1rem; }

        .rel-card {
            display: flex;
            gap: 1.25rem;
            padding: 1.25rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 1rem;
            background: var(--bs-body-bg);
            transition: border-color .2s, box-shadow .2s;
        }
        .rel-card:hover {
            border-color: var(--bs-primary);
            box-shadow: 0 16px 38px -24px rgba(46, 140, 194, .7);
        }
        .rel-card--latest {
            border-color: var(--bs-primary);
            background:
                linear-gradient(var(--bs-body-bg), var(--bs-body-bg)) padding-box,
                radial-gradient(120% 120% at 0 0, rgba(46, 140, 194, .12), transparent 60%) border-box;
        }
        /* Отдельная страница релиза: карточка одна, подсветка по наведению только мешает */
        .rel-card--single:hover { border-color: var(--bs-border-color); box-shadow: none; }
        .rel-card--single.rel-card--latest:hover { border-color: var(--bs-primary); }

        .rel-card__aside {
            flex-shrink: 0;
            width: 120px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: .4rem;
        }
        .rel-tag {
            font-family: var(--bs-font-monospace);
            font-weight: 700;
            font-size: .95rem;
            padding: .25rem .6rem;
            border-radius: .5rem;
            color: var(--bs-primary);
            background: rgba(46, 140, 194, .12);
        }
        .rel-flag {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: .15rem .5rem;
            border-radius: 100px;
        }
        .rel-flag--latest { color: #fff; background: var(--bs-primary); }
        .rel-flag--pre { color: var(--bs-warning); border: 1px solid var(--bs-warning); }

        .rel-card__body { flex: 1; min-width: 0; overflow-wrap: anywhere; }
        .rel-card__title { font-size: 1.15rem; font-weight: 700; margin-bottom: .35rem; }
        .rel-card__title a { color: var(--bs-body-color); text-decoration: none; }
        .rel-card__title a:hover { color: var(--bs-primary); }

        .rel-meta {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: var(--bs-secondary-color);
            margin-bottom: .75rem;
        }
        .rel-meta__avatar { width: 22px; height: 22px; }
        .rel-meta__author { color: var(--bs-secondary-color); text-decoration: none; }
        .rel-meta__author:hover { color: var(--bs-primary); }
        .rel-meta__sep { opacity: .5; }
        .rel-meta__link { color: var(--bs-secondary-color); text-decoration: none; }
        .rel-meta__link:hover { color: var(--bs-primary); }

        .rel-spoiler { margin-bottom: .85rem; }
        .rel-spoiler summary { cursor: pointer; color: var(--bs-primary); font-size: .9rem; }
        .rel-spoiler__inner { margin-top: .5rem; font-size: .92rem; color: var(--bs-secondary-color); }
        .rel-body { margin-bottom: .85rem; font-size: .92rem; }

        .rel-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
        .rel-asset__size { opacity: .75; font-size: .8rem; margin-left: .25rem; }
        .rel-asset__count { font-size: .82rem; color: var(--bs-secondary-color); }

        @media (max-width: 575.98px) {
            .rel-feed { gap: .85rem; }
            .rel-card { flex-direction: column; gap: .85rem; padding: 1rem; border-radius: .85rem; }
            .rel-card__aside { flex-direction: row; align-items: center; flex-wrap: wrap; width: auto; gap: .5rem; }
            .rel-card__title { font-size: 1.05rem; }

            .rel-actions { gap: .5rem; }
            /* На узких экранах кнопки разной длины текста ломались на разное число строк
               из-за flex: 1 1 0 — высота расходилась. Полная ширина убирает перенос. */
            .rel-actions .btn-primary,
            .rel-actions .btn-outline-primary,
            .rel-actions .btn-outline-secondary { flex: 1 1 100%; text-align: center; }
            .rel-asset__count { flex: 1 1 100%; }
        }
    </style>
@endpush
