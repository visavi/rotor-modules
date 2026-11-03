<div class="form-check form-switch mb-3">
    <input type="checkbox" role="switch" class="form-check-input{{ hasError('privacy') }}" value="1" name="privacy" id="privacy"{{ old('privacy') ? ' checked' : '' }} required>
    <label class="form-check-label" for="privacy">
        {!! __('privacy::privacy.accept', ['link' => '<a href="' . route('privacy.index') . '" target="_blank">' . e(__('privacy::privacy.accept_link')) . '</a>']) !!}
    </label>
    <div class="invalid-feedback">{{ textError('privacy') }}</div>
</div>
