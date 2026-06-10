@if(! empty($providers))
<div class="mb-3">
    <div class="text-muted mb-2 small">{{ __('social_auth::social_auth.login_via') }}:</div>
    <div class="d-flex gap-3 flex-wrap">
        @foreach($providers as $provider)
            @php $cfg = \Modules\SocialAuth\Models\Social::providerConfig($provider) @endphp
            <a href="{{ route('social.redirect', ['provider' => $provider]) }}"
               class="d-flex flex-column align-items-center gap-1 text-decoration-none text-muted"
               title="{{ $cfg['name'] }}">
                <i class="{{ $cfg['icon'] }}" style="color: {{ $cfg['color'] }}"></i>
                <span class="small">{{ $cfg['name'] }}</span>
            </a>
        @endforeach
    </div>
</div>
@endif
