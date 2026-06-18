@extends('layout')

@section('titlebar', '')

@section('content')
    <section class="rotor-hero">
        <div class="rotor-hero__bg" aria-hidden="true"></div>

        <div class="rotor-hero__inner">
            @if ($release)
                <a class="rotor-badge" href="/rotor/releases">
                    <span class="rotor-badge__dot"></span>
                    {{ __('docs::rotor.new_version', ['version' => $release['tag_name']]) }}
                    <i class="fas fa-arrow-right ms-1"></i>
                </a>
            @endif

            <img src="/assets/modules/docs/rotor.png" alt="RotorCMS" class="rotor-hero__logo">

            <h1 class="rotor-hero__title">{{ __('docs::rotor.hero_title') }}</h1>

            <p class="rotor-hero__lead">
                {{ __('docs::rotor.hero_lead') }}
            </p>

            <div class="rotor-install">
                <span class="rotor-install__prompt">$</span>
                <input class="rotor-install__field" type="text" readonly
                       value="composer create-project visavi/rotor .">
                <button class="rotor-install__copy" type="button"
                        onclick="return copyToClipboard(this)"
                        data-bs-toggle="tooltip" title="{{ __('main.copy') }}">
                    <i class="far fa-clipboard"></i>
                </button>
            </div>

            <div class="rotor-cta">
                <a href="/docs" class="btn btn-primary btn-lg rotor-cta__primary">
                    <i class="fa-solid fa-book-open me-1"></i> {{ __('docs::rotor.documentation') }}
                </a>
                <a href="https://github.com/visavi/rotor" class="btn btn-lg rotor-cta__ghost" rel="noopener" target="_blank">
                    <i class="fab fa-github me-1"></i> GitHub
                </a>
            </div>

            <p class="rotor-meta">
                @if ($release)
                    {{ __('docs::rotor.current_version') }} <strong>{{ $release['tag_name'] }}</strong>
                    <span class="rotor-meta__sep">&middot;</span>
                @endif
                <a href="/rotor/releases">{{ __('docs::rotor.releases') }}</a>
                <span class="rotor-meta__sep">&middot;</span>
                <a href="/rotor/commits">{{ __('docs::rotor.commits') }}</a>
                <span class="rotor-meta__sep">&middot;</span>
                <a href="/rotor/modules">{{ __('docs::rotor.modules') }}</a>
            </p>
        </div>
    </section>

    <section class="rotor-features">
        @php
            $icons = ['fa-bolt', 'fa-server', 'fa-puzzle-piece', 'fa-layer-group', 'fa-palette', 'fa-code-branch'];
            $items = __('docs::rotor.features');
            $features = is_array($items)
                ? array_map(static fn ($feature, $icon) => $feature + ['icon' => $icon], $items, $icons)
                : [];
        @endphp

        <div class="row g-3 g-md-4">
            @foreach ($features as $i => $feature)
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="rotor-card" style="--d: {{ $i * 60 }}ms">
                        <div class="rotor-card__icon">
                            <i class="fas {{ $feature['icon'] }}"></i>
                        </div>
                        <h3 class="rotor-card__title">{{ $feature['title'] }}</h3>
                        <p class="rotor-card__text">{{ $feature['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@stop

@push('styles')
    <style>
        .rotor-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1.5rem;
            padding: clamp(2.5rem, 3vw, 5rem) 1.25rem clamp(2rem, 4vw, 3rem);
            isolation: isolate;
        }
        .rotor-hero__bg {
            position: absolute;
            inset: 0;
            z-index: -1;
            background:
                radial-gradient(60% 55% at 50% -10%, rgba(46, 140, 194, .28), transparent 70%),
                radial-gradient(45% 40% at 85% 20%, rgba(46, 140, 194, .18), transparent 70%),
                radial-gradient(40% 45% at 10% 30%, rgba(120, 200, 230, .15), transparent 70%);
        }
        .rotor-hero__bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(var(--bs-border-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--bs-border-color) 1px, transparent 1px);
            background-size: 38px 38px;
            opacity: .25;
            mask-image: radial-gradient(70% 70% at 50% 30%, #000 30%, transparent 75%);
            -webkit-mask-image: radial-gradient(70% 70% at 50% 30%, #000 30%, transparent 75%);
        }
        .rotor-hero__inner {
            max-width: 720px;
            margin: 0 auto;
            text-align: center;
        }
        .rotor-hero__inner > * { animation: rotor-up .6s both; }
        .rotor-hero__inner > *:nth-child(1) { animation-delay: .02s; }
        .rotor-hero__inner > *:nth-child(2) { animation-delay: .08s; }
        .rotor-hero__inner > *:nth-child(3) { animation-delay: .14s; }
        .rotor-hero__inner > *:nth-child(4) { animation-delay: .20s; }
        .rotor-hero__inner > *:nth-child(5) { animation-delay: .26s; }
        .rotor-hero__inner > *:nth-child(6) { animation-delay: .32s; }
        .rotor-hero__inner > *:nth-child(7) { animation-delay: .38s; }

        .rotor-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-size: .8rem;
            font-weight: 600;
            padding: .35rem .85rem;
            border-radius: 100px;
            border: 1px solid var(--bs-border-color);
            background: var(--bs-body-bg);
            color: var(--bs-body-color);
            text-decoration: none;
            margin-bottom: 1.75rem;
            transition: border-color .2s, transform .2s;
        }
        .rotor-badge:hover { border-color: var(--bs-primary); transform: translateY(-1px); color: var(--bs-body-color); }
        .rotor-badge:hover .fa-arrow-right { transform: translateX(3px); }
        .rotor-badge .fa-arrow-right { font-size: .7rem; transition: transform .2s; }
        .rotor-badge__dot {
            width: .5rem; height: .5rem; border-radius: 50%;
            background: var(--bs-primary);
            box-shadow: 0 0 0 0 rgba(46, 140, 194, .6);
            animation: rotor-pulse 2s infinite;
        }

        .rotor-hero__logo { display: block; width: 200px; height: auto; margin: 1rem auto 1.5rem; }

        .rotor-hero__title {
            font-size: clamp(1.6rem, 4vw, 2.5rem);
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -.02em;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--bs-body-color) 30%, var(--bs-primary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .rotor-hero__lead {
            font-size: clamp(1rem, 2.2vw, 1.2rem);
            color: var(--bs-secondary-color);
            max-width: 560px;
            margin: 0 auto 2rem;
        }

        .rotor-install {
            display: flex;
            align-items: center;
            gap: .5rem;
            max-width: 460px;
            margin: 0 auto 1.75rem;
            padding: .35rem .35rem .35rem .9rem;
            border-radius: .9rem;
            border: 1px solid var(--bs-border-color);
            background: var(--bs-tertiary-bg);
            font-family: var(--bs-font-monospace);
        }
        .rotor-install__prompt { color: var(--bs-primary); font-weight: 700; }
        .rotor-install__field {
            flex: 1;
            min-width: 0;
            border: 0;
            background: transparent;
            color: var(--bs-body-color);
            font-size: .9rem;
            font-family: inherit;
            outline: none;
        }
        .rotor-install__copy {
            flex-shrink: 0;
            width: 38px; height: 38px;
            border: 0;
            border-radius: .6rem;
            background: var(--bs-primary);
            color: #fff;
            cursor: pointer;
            transition: filter .2s, transform .15s;
        }
        .rotor-install__copy:hover { filter: brightness(1.08); }
        .rotor-install__copy:active { transform: scale(.94); }

        .rotor-cta {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .75rem;
            margin-bottom: 1.5rem;
        }
        .rotor-cta__primary { padding-inline: 1.5rem; box-shadow: 0 8px 24px -10px rgba(46, 140, 194, .8); }
        .rotor-cta__ghost {
            padding-inline: 1.5rem;
            border: 1px solid var(--bs-border-color);
            background: var(--bs-body-bg);
            color: var(--bs-body-color);
        }
        .rotor-cta__ghost:hover { border-color: var(--bs-body-color); background: var(--bs-tertiary-bg); color: var(--bs-body-color); }

        .rotor-meta { font-size: .9rem; color: var(--bs-secondary-color); margin-bottom: 0; }
        .rotor-meta a { color: var(--bs-secondary-color); text-decoration: none; }
        .rotor-meta a:hover { color: var(--bs-primary); }
        .rotor-meta__sep { padding-inline: .4rem; opacity: .5; }

        .rotor-card {
            height: 100%;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--bs-border-color);
            background: var(--bs-body-bg);
            transition: transform .25s, border-color .25s, box-shadow .25s;
            animation: rotor-up .6s both;
            animation-delay: var(--d, 0ms);
        }
        .rotor-card:hover {
            transform: translateY(-4px);
            border-color: var(--bs-primary);
            box-shadow: 0 18px 40px -22px rgba(46, 140, 194, .7);
        }
        .rotor-card__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px; height: 48px;
            border-radius: .85rem;
            margin-bottom: 1rem;
            font-size: 1.25rem;
            color: var(--bs-primary);
            background: rgba(46, 140, 194, .12);
        }
        .rotor-card__title { font-size: 1.1rem; font-weight: 700; margin-bottom: .4rem; }
        .rotor-card__text { font-size: .92rem; color: var(--bs-secondary-color); margin-bottom: 0; }

        @keyframes rotor-up {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes rotor-pulse {
            0%   { box-shadow: 0 0 0 0 rgba(46, 140, 194, .55); }
            70%  { box-shadow: 0 0 0 6px rgba(46, 140, 194, 0); }
            100% { box-shadow: 0 0 0 0 rgba(46, 140, 194, 0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .rotor-hero__inner > *, .rotor-card { animation: none; }
            .rotor-badge__dot { animation: none; }
        }
    </style>
@endpush
