@foreach ($fields as $field)
    <x-profile.field
        :label="$field->name"
        :value="$field->type === 'textarea' ? renderHtml($field->value) : e($field->value)"
    />
@endforeach
