@use('Illuminate\Support\Str')

{{-- Поля записи для своего вида: имя поля и что в нём у последней записи выбранных разделов --}}
@php($rows = [
    'title'      => $entry->title,
    'url'        => $entry->url,
    'date'       => $entry->date ? dateFixed($entry->date) : null,
    'text'       => $entry->text,
    'image'      => $entry->image,
    'author'     => $entry->author?->getName(),
    'section'    => $entry->section,
    'sectionUrl' => $entry->sectionUrl,
    'comments'   => $entry->comments,
    'rating'     => $entry->rating,
    'model'      => class_basename($entry->model) . ' #' . $entry->model->getKey(),
])

<details class="mb-3">
    <summary>{{ __('home_builder::home_builder.fields') }}</summary>

    <div class="form-text mb-2">{{ __('home_builder::home_builder.fields_hint') }}</div>

    <div class="table-responsive">
        <table class="table table-sm small mb-0">
            <tbody>
                @foreach ($rows as $field => $value)
                    <tr>
                        <td class="text-nowrap"><code>$entry->{{ $field }}</code></td>
                        <td class="text-break">
                            @if ($value === null || $value === '')
                                <span class="text-muted">null</span>
                            @else
                                {{ Str::limit((string) $value, 120) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</details>
