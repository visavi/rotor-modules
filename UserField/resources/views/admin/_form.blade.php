<div class="mb-3">
    <label for="type" class="form-label">{{ __('main.type') }}:</label>

    <?php
        $inputType = old('type', $field->type) ?: $types[0];
        $limitTypes = \Modules\UserField\Models\UserField::LIMIT_TYPES;
        $optionTypes = \Modules\UserField\Models\UserField::OPTION_TYPES;
        $showLimit = in_array($inputType, $limitTypes, true);
        $showOptions = in_array($inputType, $optionTypes, true);
    ?>
    <select class="form-select{{ hasError('type') }}" name="type" id="type">
        @foreach ($types as $type)
            <?php $selected = ($type === $inputType) ? ' selected' : ''; ?>
            <option value="{{ $type }}"{{ $selected }}>{{ __('user_field::user_fields.' . $type) }}</option>
        @endforeach
    </select>

    <div class="invalid-feedback">{{ textError('type') }}</div>
    @if ($answersCount ?? 0)
        {{-- Видно, только пока выбран не сохранённый тип --}}
        <div class="form-text text-danger{{ $inputType === $field->type ? ' d-none' : '' }}" id="type-warning" data-type="{{ $field->type }}">
            {{ __('user_field::user_fields.type_change_warning', ['count' => $answersCount]) }}
        </div>
    @endif
</div>

<div class="mb-3">
    <label for="name" class="form-label">{{ __('main.title') }}:</label>
    <input type="text" name="name" class="form-control{{ hasError('name') }}" id="name" maxlength="50" value="{{ old('name', $field->name) }}" required>
    <div class="invalid-feedback">{{ textError('name') }}</div>
</div>

<div class="mb-3{{ $showLimit ? '' : ' d-none' }}" data-types="{{ implode(',', $limitTypes) }}">
    <label for="placeholder" class="form-label">{{ __('user_field::user_fields.placeholder') }}:</label>
    <input type="text" name="placeholder" class="form-control{{ hasError('placeholder') }}" id="placeholder" maxlength="100" value="{{ old('placeholder', $field->placeholder) }}"{{ $showLimit ? '' : ' disabled' }}>
    <div class="invalid-feedback">{{ textError('placeholder') }}</div>
    <div class="form-text">{{ __('user_field::user_fields.placeholder_help') }}</div>
</div>

<div class="mb-3">
    <label for="hint" class="form-label">{{ __('user_field::user_fields.hint') }}:</label>
    <input type="text" name="hint" class="form-control{{ hasError('hint') }}" id="hint" maxlength="255" value="{{ old('hint', $field->hint) }}">
    <div class="invalid-feedback">{{ textError('hint') }}</div>
    <div class="form-text">{{ __('user_field::user_fields.hint_help') }}</div>
</div>

<div class="mb-3{{ $showOptions ? '' : ' d-none' }}" data-types="{{ implode(',', $optionTypes) }}">
    <label for="options" class="form-label">{{ __('user_field::user_fields.options') }}:</label>
    <textarea name="options" class="form-control{{ hasError('options') }}" id="options" rows="4"{{ $showOptions ? '' : ' disabled' }}>{{ old('options', $field->options) }}</textarea>
    <div class="invalid-feedback">{{ textError('options') }}</div>
    <div class="form-text">{{ __('user_field::user_fields.options_help') }}</div>
</div>

<div class="mb-3{{ $showLimit ? '' : ' d-none' }}" data-types="{{ implode(',', $limitTypes) }}">
    <label for="min" class="form-label">{{ __('main.min') }}:</label>
    <input type="number" name="min" class="form-control{{ hasError('min') }}" id="min" value="{{ old('min', $field->min) }}"{{ $showLimit ? '' : ' disabled' }} required>
    <div class="invalid-feedback">{{ textError('min') }}</div>
</div>

<div class="mb-3{{ $showLimit ? '' : ' d-none' }}" data-types="{{ implode(',', $limitTypes) }}">
    <label for="max" class="form-label">{{ __('main.max') }}:</label>
    <input type="number" name="max" class="form-control{{ hasError('max') }}" id="max" value="{{ old('max', $field->max) }}"{{ $showLimit ? '' : ' disabled' }} required>
    <div class="invalid-feedback">{{ textError('max') }}</div>
    <div class="form-text">{{ __('user_field::user_fields.min_max_help') }}</div>
</div>

<div class="form-check form-switch mb-3">
    <input type="hidden" value="0" name="required">
    <input type="checkbox" class="form-check-input" role="switch" value="1" name="required" id="required"{{ old('required', $field->required) ? ' checked' : '' }}>
    <label class="form-check-label" for="required">{{ __('user_field::user_fields.required') }}</label>
</div>

<button class="btn btn-primary">{{ __('main.save') }}</button>

@push('scripts')
    <script>
        // Настройки, которых у выбранного типа нет, прячутся и не отправляются
        (() => {
            const type = document.getElementById('type')
            const warning = document.getElementById('type-warning')
            const toggle = () => {
                document.querySelectorAll('[data-types]').forEach(group => {
                    const shown = group.dataset.types.split(',').includes(type.value)
                    group.classList.toggle('d-none', !shown)
                    group.querySelectorAll('input, textarea').forEach(el => el.disabled = !shown)
                })

                warning?.classList.toggle('d-none', type.value === warning.dataset.type)
            }

            type.addEventListener('change', toggle)
            // Браузер восстанавливает выбор при возврате назад — сверяемся с ним
            toggle()
        })()
    </script>
@endpush
