@foreach($fields as $field)
    {{ $field->name }}:
    @if ($field->type === 'textarea')
        {{ renderHtml($field->value) }}
    @else
        {{ $field->value }}
    @endif
    <br>
@endforeach
