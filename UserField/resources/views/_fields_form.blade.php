@foreach($fields as $field)
    <div class="mb-3{{ $field->required ? ' form-required' : null }}{{ hasError('field' . $field->id) }}">
        <label for="{{ 'field' . $field->id }}" class="form-label">{{ $field->name }}:</label>
        @if ($field->type === 'textarea')
            <textarea class="form-control tiptap" id="{{ 'field' . $field->id }}" cols="25" rows="5" name="{{ 'field' . $field->id }}">{{ getInput('field' . $field->id, $field->value) }}</textarea>
        @else
            <input class="form-control" id="{{ 'field' . $field->id }}" name="{{ 'field' . $field->id }}" maxlength="{{ $field->max }}" value="{{ getInput('field' . $field->id, $field->value) }}">
        @endif
        <div class="invalid-feedback">{{ textError('field' . $field->id) }}</div>
    </div>
@endforeach
