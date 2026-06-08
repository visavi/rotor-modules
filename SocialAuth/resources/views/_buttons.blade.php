@if(! empty($providers))
@php
$providerNames = ['google' => 'Google', 'github' => 'GitHub', 'yandex' => 'Яндекс', 'vk' => 'ВКонтакте'];
$providerIcons = ['google' => 'fab fa-google fa-2x', 'github' => 'fab fa-github fa-2x', 'yandex' => 'fab fa-yandex fa-2x', 'vk' => 'fab fa-vk fa-2x'];
$providerColors = ['google' => '#4285F4', 'github' => 'currentColor', 'yandex' => '#FC3F1D', 'vk' => '#0077FF'];
@endphp
<div class="mb-3">
    <div class="text-muted mb-2 small">{{ __('social_auth::social_auth.login_via') }}:</div>
    <div class="d-flex gap-3 flex-wrap">
        @foreach($providers as $provider)
            <a href="{{ route('social.redirect', ['provider' => $provider]) }}"
               class="d-flex flex-column align-items-center gap-1 text-decoration-none text-muted"
               title="{{ $providerNames[$provider] ?? ucfirst($provider) }}">
                <i class="{{ $providerIcons[$provider] ?? 'fab fa-' . $provider . ' fa-2x' }}"
                   style="color: {{ $providerColors[$provider] ?? 'currentColor' }}"></i>
                <span class="small">{{ $providerNames[$provider] ?? ucfirst($provider) }}</span>
            </a>
        @endforeach
    </div>
</div>
@endif
