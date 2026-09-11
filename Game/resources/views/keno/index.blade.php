@extends('layout')

@section('title', __('game::games.keno'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.keno') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @php ['field' => $field, 'draw' => $draw, 'picks' => $picks] = $limits; @endphp

    {{ __('game::games.keno_intro', ['picks' => $picks, 'field' => $field, 'draw' => $draw]) }}<br><br>

    @include('game::keno/_board')

    <br>
    <i class="fa fa-question-circle"></i> <a href="/games/keno/rules">{{ __('game::games.rules') }}</a>
@stop

@push('styles')
    <style>
        .keno-field {
            display: grid;
            grid-template-columns: repeat(8, minmax(0, 1fr));
            gap: 5px;
            max-width: 440px;
        }

        /* На узком экране десять колонок не читаются, поэтому поле складывается вдвое */
        @media (max-width: 576px) {
            .keno-field { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        }

        .keno-cell input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .keno-cell span {
            display: block;
            padding: 0.35rem 0;
            font-size: 0.85rem;
            text-align: center;
            border: 1px solid rgba(128, 128, 128, 0.4);
            border-radius: 0.25rem;
            cursor: pointer;
            user-select: none;
        }

        .keno-cell input:checked + span {
            color: #fff;
            font-weight: 700;
            background: #0d6efd;
            border-color: #0d6efd;
        }

        /* Вытянутое число подсвечивается зелёным, совпавшее с отметкой — поверх синего */
        .keno-drawn span {
            animation: keno-hit 0.25s ease-out forwards;
            animation-delay: calc(var(--step) * 0.18s);
        }

        .keno-late {
            animation: keno-appear 0.3s ease-out backwards;
            animation-delay: calc(var(--last) * 0.18s);
        }

        .keno-balance {
            display: grid;
        }

        .keno-balance > * {
            grid-area: 1 / 1;
        }

        .keno-balance-old {
            animation: keno-gone 0.1s linear forwards;
            animation-delay: calc(var(--last) * 0.18s);
        }

        @keyframes keno-hit {
            from { transform: scale(1); }
            50% { transform: scale(1.25); }
            to {
                transform: scale(1);
                color: #fff;
                background: #198754;
                border-color: #198754;
            }
        }

        @keyframes keno-appear {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes keno-gone {
            to { opacity: 0; visibility: hidden; }
        }

        @media (prefers-reduced-motion: reduce) {
            .keno-drawn span,
            .keno-late,
            .keno-balance-old {
                animation-duration: 0.01s;
                animation-delay: 0s;
            }
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        // Поле подменяется ajax-ом, поэтому слушатели висят на документе,
        // а элементы ищутся заново при каждом событии
        const max = {{ $picks }};
        const template = @json(__('game::games.keno_picked', ['count' => ':count', 'picks' => $picks]));

        const boxes = () => [...document.querySelectorAll('.keno-field input')]

        const recount = () => {
            const counter = document.querySelector('.keno-counter')

            if (counter) {
                counter.textContent = template.replace(':count', String(boxes().filter(box => box.checked).length))
            }
        }

        document.addEventListener('change', (event) => {
            if (! event.target.closest('.keno-field')) {
                return
            }

            // Больше максимума не отметить: лишняя клетка просто не отмечается
            if (boxes().filter(box => box.checked).length > max) {
                event.target.checked = false
            }

            recount()
        })

        document.addEventListener('click', (event) => {
            if (! event.target.closest('.keno-clear')) {
                return
            }

            boxes().forEach(box => box.checked = false)
            recount()
        })
    </script>
@endpush
