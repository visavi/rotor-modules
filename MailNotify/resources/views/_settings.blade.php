<div class="section-form mb-3 shadow">
    <div class="section-title"><i class="fas fa-envelope"></i> {{ __('mail_notify::mail_notify.title') }}</div>

    <div class="form-check form-switch mb-2">
        <input type="hidden" value="0" name="subscribe">
        <input type="checkbox" class="form-check-input" value="1" name="subscribe" id="subscribe"{{ old('subscribe', $user->subscribe) ? ' checked' : '' }}>
        <label class="form-check-label" for="subscribe">
            {{ __('mail_notify::mail_notify.receive_newsletters') }}
            <i class="fas fa-circle-question text-muted" data-bs-toggle="tooltip" title="{{ __('mail_notify::mail_notify.newsletters_hint') }}"></i>
        </label>
    </div>
</div>
