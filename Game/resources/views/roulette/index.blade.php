@extends('layout')

@section('title', __('game::games.roulette'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.roulette') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    {{ __('game::games.roulette_intro') }}<br><br>

    @include('game::roulette/_wheel')

    <br>

    {{ __('game::games.roulette_payouts') }}
@stop

@push('styles')
    <style>
        .roulette-wheel-holder {
            position: relative;
            max-width: 320px;
        }

        .roulette-wheel {
            width: 100%;
            height: auto;
        }

        /* Один оборот колеса задаёт темп всей странице: результат ждёт его конца */
        .roulette-spinning {
            animation: roulette-spin 4s cubic-bezier(0.15, 0.85, 0.25, 1) forwards;
        }

        .roulette-late {
            animation: roulette-appear 0.3s ease-out backwards;
            animation-delay: 4s;
        }

        .roulette-balance {
            display: grid;
        }

        .roulette-balance > * {
            grid-area: 1 / 1;
        }

        .roulette-balance-old {
            animation: roulette-gone 0.1s linear forwards;
            animation-delay: 4s;
        }

        @keyframes roulette-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(var(--angle, 0deg)); }
        }

        @keyframes roulette-appear {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes roulette-gone {
            to { opacity: 0; visibility: hidden; }
        }

        .roulette-pointer {
            position: absolute;
            top: -4px;
            left: 50%;
            z-index: 1;
            width: 0;
            height: 0;
            transform: translateX(-50%);
            border-top: 16px solid #ffc107;
            border-right: 9px solid transparent;
            border-left: 9px solid transparent;
        }

        .roulette-sector.roulette-red { fill: #c62828; }
        .roulette-sector.roulette-black { fill: #212529; }
        .roulette-sector.roulette-zero { fill: #2e7d32; }
        .roulette-sector { stroke: #f8f9fa; stroke-width: 0.5; }
        .roulette-rim { fill: #6d4c41; }
        .roulette-hub { fill: #8d6e63; }

        .roulette-label {
            font-size: 12px;
            font-weight: 700;
            fill: #fff;
            text-anchor: middle;
            dominant-baseline: middle;
        }

        .roulette-result {
            display: inline-block;
            min-width: 2.5rem;
            padding: 0.25rem 0.5rem;
            color: #fff;
            font-size: 1.5rem;
            text-align: center;
            border-radius: 0.25rem;
        }

        .roulette-result.roulette-red { background: #c62828; }
        .roulette-result.roulette-black { background: #212529; }
        .roulette-result.roulette-zero { background: #2e7d32; }

        @media (prefers-reduced-motion: reduce) {
            .roulette-spinning,
            .roulette-late,
            .roulette-balance-old {
                animation-duration: 0.01s;
                animation-delay: 0s;
            }
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        // Форма подменяется ajax-ом, поэтому слушатель висит на документе.
        // Начальное состояние поля приходит с сервера атрибутом hidden
        document.addEventListener('change', (event) => {
            const type = event.target.closest('#type')

            if (! type) {
                return
            }

            document.querySelectorAll('.roulette-number-field').forEach(field => {
                field.hidden = type.value !== 'number'
            })
        })
    </script>
@endpush
