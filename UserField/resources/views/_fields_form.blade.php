@foreach($fields as $field)
    <?php
        $name = 'field' . $field->id;
        $value = old($name, $field->value);
    ?>
    <div class="mb-3{{ $field->required ? ' form-required' : null }}{{ hasError($name) }}">
        {{-- Поле приходит всегда: выключенный переключатель, невыбранное радио и список
             с приглашением иначе не попали бы в запрос, и их нельзя было бы очистить --}}
        <input type="hidden" name="{{ $name }}" value="">

        @unless ($field->type === 'checkbox')
            <label for="{{ $name }}" class="form-label">{{ $field->name }}:</label>
        @endunless

        @if ($field->type === 'checkbox')
            {{-- Подпись переключателя — само название поля --}}
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" role="switch" id="{{ $name }}" name="{{ $name }}" value="1"{{ $value ? ' checked' : '' }}>
                <label class="form-check-label" for="{{ $name }}">{{ $field->name }}</label>
            </div>
        @elseif ($field->type === 'textarea')
            <textarea class="form-control tiptap" id="{{ $name }}" cols="25" rows="5" name="{{ $name }}" placeholder="{{ $field->placeholder }}">{{ $value }}</textarea>
        @elseif ($field->type === 'select')
            <select class="form-select" id="{{ $name }}" name="{{ $name }}">
                @if ($field->required)
                    {{-- Приглашение видно, пока ничего не выбрано, выбрать его как ответ нельзя --}}
                    <option value="" disabled{{ blank($value) ? ' selected' : '' }}>{{ __('user_field::user_fields.select_choose') }}</option>
                @else
                    {{-- Пустой вариант очищает значение --}}
                    <option value="">{{ __('user_field::user_fields.select_none') }}</option>
                @endif
                @foreach ($field->optionList() as $option)
                    <option value="{{ $option }}"{{ $option === $value ? ' selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
        @elseif ($field->type === 'radio')
            @unless ($field->required)
                {{-- Выбранную радиокнопку не снять, поэтому у необязательного поля есть пустой вариант --}}
                <div class="form-check">
                    <input type="radio" class="form-check-input" id="{{ $name }}_none" name="{{ $name }}" value=""{{ blank($value) ? ' checked' : '' }}>
                    <label class="form-check-label" for="{{ $name }}_none">{{ __('user_field::user_fields.select_none') }}</label>
                </div>
            @endunless
            @foreach ($field->optionList() as $index => $option)
                <div class="form-check">
                    <input type="radio" class="form-check-input" id="{{ $name }}_{{ $index }}" name="{{ $name }}" value="{{ $option }}"{{ $option === $value ? ' checked' : '' }}>
                    <label class="form-check-label" for="{{ $name }}_{{ $index }}">{{ $option }}</label>
                </div>
            @endforeach
        @elseif ($field->type === 'number')
            <input type="number" class="form-control" id="{{ $name }}" name="{{ $name }}" min="{{ $field->min }}" max="{{ $field->max }}" step="any" placeholder="{{ $field->placeholder }}" value="{{ $value }}">
        @elseif ($field->type === 'date')
            <input type="date" class="form-control" id="{{ $name }}" name="{{ $name }}" value="{{ $value }}">
        @else
            {{-- input, url, email, tel: тип подсказывает браузеру клавиатуру и формат --}}
            <input type="{{ $field->type === 'input' ? 'text' : $field->type }}" class="form-control" id="{{ $name }}" name="{{ $name }}" maxlength="{{ $field->max }}" placeholder="{{ $field->placeholder }}" value="{{ $value }}">
        @endif
        <div class="invalid-feedback">{{ textError($name) }}</div>
        @if ($field->hint)
            <div class="form-text">{{ $field->hint }}</div>
        @endif
    </div>
@endforeach
