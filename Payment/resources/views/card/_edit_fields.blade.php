{{-- Поля карточки: общие для покупки, правки владельцем и админки --}}
<div class="mb-3">
    <label for="title" class="form-label">{{ __('payment::payments.cards.heading') }}:</label>
    <input class="form-control{{ hasError('title') }}" id="title" name="title" type="text" value="{{ old('title', $card->names[0] ?? '') }}" minlength="5" maxlength="60" required>
    <div class="invalid-feedback">{{ textError('title') }}</div>
</div>

<div class="mb-3">
    <label for="text" class="form-label">{{ __('payment::payments.cards.text') }}:</label>
    <textarea class="form-control{{ hasError('text') }}" id="text" name="text" rows="3" maxlength="200">{{ old('text', $card->text) }}</textarea>
    <div class="invalid-feedback">{{ textError('text') }}</div>
</div>

<div class="mb-3">
    <label for="url" class="form-label">{{ __('payment::payments.cards.url') }}:</label>
    <input class="form-control{{ hasError('url') }}" id="url" name="url" type="text" value="{{ old('url', $card->url) }}" maxlength="100" placeholder="https://" required>
    <div class="invalid-feedback">{{ textError('url') }}</div>
</div>

<div class="mb-3">
    <label for="image" class="form-label">{{ __('payment::payments.cards.image') }}:</label>

    @if ($card->image)
        <div class="mb-2">
            <img src="{{ $card->image }}" class="rounded" width="96" height="96" alt="">
            <div class="form-check mt-1">
                <input type="checkbox" class="form-check-input" value="1" name="remove_image" id="remove_image">
                <label class="form-check-label" for="remove_image">{{ __('payment::payments.cards.remove_image') }}</label>
            </div>
        </div>
    @endif

    <input class="form-control{{ hasError('image') }}" id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp">
    <div class="invalid-feedback">{{ textError('image') }}</div>
    <div class="form-text">{{ __('payment::payments.cards.image_hint') }}</div>
</div>
