<?php

declare(strict_types=1);

namespace Modules\PageEditor\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\PageEditor\Support\PathResolver;

/**
 * Загрузка файла в редактор
 */
class FileUploadRequest extends FormRequest
{
    public function rules(): array
    {
        // Картинки и шрифты редактор не открывает, но положить их в корень нужно,
        // поэтому список расширений шире редактируемых
        $extensions = array_unique(array_merge(
            (array) config('page_editor.editable', []),
            (array) config('page_editor.uploadable', []),
        ));

        return [
            'file' => [
                'required',
                'file',
                'extensions:' . implode(',', $extensions),
                'max:' . intdiv($this->maxSize(), 1024),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! preg_match(FileCreateRequest::NAME_PATTERN, $this->fileName())) {
                        $fail(__('page_editor::files.file_invalid'));
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.extensions' => __('validator.extension'),
            'file.max'        => __('validator.size_max', ['size' => formatSize($this->maxSize())]),
            'file.*'          => __('page_editor::files.file_invalid'),
        ];
    }

    /**
     * Имя загруженного файла без каталогов
     */
    public function fileName(): string
    {
        $file = $this->file('file');

        return $file ? PathResolver::normalize($file->getClientOriginalName()) : '';
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

    private function maxSize(): int
    {
        return (int) config('page_editor.max_upload_size', 5242880);
    }
}
