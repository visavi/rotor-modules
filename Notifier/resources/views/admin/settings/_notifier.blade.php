@extends('admin/settings/layout')

@section('title', __('notifier::notifier.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'Notifier']) }}">{{ __('admin.modules.module') }} {{ __('notifier::notifier.notifier') }}</a></li>
            <li class="breadcrumb-item active">{{ __('notifier::notifier.settings') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('notifier::notifier.settings') }}</h1>
@stop

@section('settings')
    @php
        $interval = (int) old('sets.notifier_interval', $settings['notifier_interval'] ?? \Modules\Notifier\Support\Notifier::DEFAULT_INTERVAL);
        $sound = (string) old('sets.notifier_sound', $settings['notifier_sound'] ?? '');
        $volume = (int) old('sets.notifier_volume', $settings['notifier_volume'] ?? 70);
    @endphp

    <form method="post" action="{{ route('notifier.settings.update') }}">
        @csrf

        <div class="form-check form-switch mb-4">
            <input type="hidden" value="0" name="sets[notifier_active]">
            <input type="checkbox" role="switch" class="form-check-input" value="1" name="sets[notifier_active]" id="notifier_active"{{ old('sets.notifier_active', $settings['notifier_active'] ?? 0) ? ' checked' : '' }}>
            <label for="notifier_active" class="form-check-label">{{ __('notifier::notifier.active') }}</label>
            <div class="form-text">{{ __('notifier::notifier.active_help') }}</div>
        </div>

        <fieldset id="notifier_options">

        <div class="mb-3{{ hasError('sets[notifier_interval]') }}">
            <label for="notifier_interval" class="form-label">{{ __('notifier::notifier.interval') }}, {{ __('notifier::notifier.seconds') }}:</label>
            <input type="number" class="form-control" id="notifier_interval" name="sets[notifier_interval]" list="notifier_intervals" min="{{ \Modules\Notifier\Support\Notifier::MIN_INTERVAL }}" max="{{ \Modules\Notifier\Support\Notifier::MAX_INTERVAL }}" step="1" value="{{ $interval }}" required>
            <datalist id="notifier_intervals">
                @foreach ($intervals as $preset)
                    <option value="{{ $preset }}"></option>
                @endforeach
            </datalist>
            <div class="form-text">{{ __('notifier::notifier.interval_help') }}</div>
            <div class="invalid-feedback">{{ textError('sets[notifier_interval]') }}</div>
        </div>

        <h2 class="h5 mt-4">{{ __('notifier::notifier.notify_group') }}</h2>
        <p class="form-text mt-0 mb-3">{{ __('notifier::notifier.notify_help') }}</p>

        <div class="mb-3{{ hasError('sets[notifier_sound]') }}">
            <label for="notifier_sound" class="form-label">{{ __('notifier::notifier.sound') }}:</label>
            <div class="input-group">
                <select class="form-select" id="notifier_sound" name="sets[notifier_sound]">
                    <option value=""{{ $sound === '' ? ' selected' : '' }}>{{ __('notifier::notifier.sound_none') }}</option>

                    @foreach ($sounds as $file => $label)
                        <option value="{{ $file }}"{{ $sound === $file ? ' selected' : '' }}>{{ $label }}</option>
                    @endforeach

                </select>
                <button class="btn btn-outline-secondary" type="button" id="notifier_play" data-base="{{ asset('assets/modules/notifiers/sounds') }}">
                    <i class="fas fa-play"></i> {{ __('notifier::notifier.sound_test') }}
                </button>
            </div>
            <div class="form-text">{{ __('notifier::notifier.sound_help') }}</div>
            <div class="invalid-feedback">{{ textError('sets[notifier_sound]') }}</div>
        </div>

        <div class="mb-3{{ hasError('sets[notifier_volume]') }}">
            <label for="notifier_volume" class="form-label">{{ __('notifier::notifier.volume') }}: <span id="notifier_volume_value">{{ $volume }}</span>%</label>
            <input type="range" class="form-range" id="notifier_volume" name="sets[notifier_volume]" min="0" max="100" step="5" value="{{ $volume }}">
            <div class="invalid-feedback d-block">{{ textError('sets[notifier_volume]') }}</div>
        </div>

        <div class="form-check form-switch mb-3">
            <input type="hidden" value="0" name="sets[notifier_title]">
            <input type="checkbox" role="switch" class="form-check-input" value="1" name="sets[notifier_title]" id="notifier_title"{{ old('sets.notifier_title', $settings['notifier_title'] ?? 0) ? ' checked' : '' }}>
            <label for="notifier_title" class="form-check-label">{{ __('notifier::notifier.title') }}</label>
            <div class="form-text">{{ __('notifier::notifier.title_help') }}</div>
        </div>

        <div class="form-check form-switch mb-3">
            <input type="hidden" value="0" name="sets[notifier_desktop]">
            <input type="checkbox" role="switch" class="form-check-input" value="1" name="sets[notifier_desktop]" id="notifier_desktop"{{ old('sets.notifier_desktop', $settings['notifier_desktop'] ?? 0) ? ' checked' : '' }}>
            <label for="notifier_desktop" class="form-check-label">{{ __('notifier::notifier.desktop') }}</label>
            <div class="form-text">{{ __('notifier::notifier.desktop_help') }}</div>
        </div>
        </fieldset>

        <button class="btn btn-primary">{{ __('main.save') }}</button>
    </form>
@stop

@push('scripts')
    <script type="module">
        const active = document.getElementById('notifier_active')
        const options = document.getElementById('notifier_options')
        const select = document.getElementById('notifier_sound')
        const volume = document.getElementById('notifier_volume')
        const output = document.getElementById('notifier_volume_value')
        const play = document.getElementById('notifier_play')

        // Остальные настройки бессмысленны при выключенной проверке
        const toggleOptions = () => {
            options.disabled = !active.checked
            options.classList.toggle('opacity-50', !active.checked)
        }

        // Без выбранного звука слушать нечего
        const togglePlay = () => {
            play.disabled = !select.value
        }

        active.addEventListener('change', toggleOptions)
        select.addEventListener('change', togglePlay)

        volume.addEventListener('input', () => {
            output.textContent = volume.value
        })

        play.addEventListener('click', () => {
            const audio = new Audio(`${play.dataset.base}/${select.value}`)
            audio.volume = Number(volume.value) / 100
            audio.play().catch(() => {})
        })

        toggleOptions()
        togglePlay()
    </script>
@endpush
