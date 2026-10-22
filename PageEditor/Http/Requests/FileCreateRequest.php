<?php

declare(strict_types=1);

namespace Modules\PageEditor\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\PageEditor\Support\PathResolver;

/**
 * Новый файл или каталог в редакторе
 *
 * Форм две: в одной имя файла, в другой имя каталога. Занято ли имя,
 * проверяет контроллер — он знает текущий каталог
 */
class FileCreateRequest extends FormRequest
{
    /**
     * Имя файла или каталога: латиница, цифры, точки между частями
     */
    public const string NAME_PATTERN = '/^[a-z0-9_\-]+(\.[a-z0-9_\-]+)*$/i';

    public function rules(): array
    {
        // Какое поле пришло, та форма и отправлена: пустое имя — ошибка своей формы
        $isFileForm = $this->has('filename');

        return [
            'filename' => [
                Rule::excludeIf(! $isFileForm),
                'required',
                'string',
                'max:255',
                'regex:' . self::NAME_PATTERN,
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (! PathResolver::isEditable($value)) {
                        $fail(__('page_editor::files.file_invalid'));
                    }
                },
            ],
            'dirname' => [Rule::excludeIf($isFileForm), 'required', 'string', 'max:255', 'regex:' . self::NAME_PATTERN],
        ];
    }

    public function messages(): array
    {
        return [
            'filename.required' => __('page_editor::files.file_required'),
            'filename.max'      => __('page_editor::files.file_required'),
            'filename.*'        => __('page_editor::files.file_invalid'),
            'dirname.required'  => __('page_editor::files.directory_required'),
            'dirname.max'       => __('page_editor::files.directory_required'),
            'dirname.*'         => __('page_editor::files.directory_invalid'),
        ];
    }

    /**
     * Ошибка возвращает на страницу создания в том же каталоге
     */
    protected function getRedirectUrl(): string
    {
        return route('admin.files.create', [
            'root' => $this->input('root', 'views'),
            'path' => PathResolver::normalize($this->string('path')->value()),
        ]);
    }

    protected function prepareForValidation(): void
    {
        // Слэши и «..» срезаются ещё до проверки: имя всегда одно звено пути
        foreach (['filename', 'dirname'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => PathResolver::normalize($this->input($field)) ?: null]);
            }
        }
    }
}
