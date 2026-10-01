@foreach ($fields as $field)
    <x-profile.field :label="$field->name" :value="$field->displayValue()" />
@endforeach
