<?php

declare(strict_types=1);

namespace Modules\Load\Http\Requests;

use App\Models\File;
use App\Rules\TextLengthRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;

/**
 * Загрузка — правка на сайте, в админке и в API
 */
class DownRequest extends FormRequest
{
    private ?Load $category = null;

    public function rules(): array
    {
        return [
            'category_id' => [
                'bail',
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $this->category = Load::query()->find($value);

                    if (! $this->category) {
                        $fail(__('load::loads.load_not_exist'));
                    } elseif ($this->category->closed && $this->category->id !== $this->currentCategoryId() && ! $this->routeIs('admin.*')) {
                        // Закрытый раздел не принимает новых, но уже лежащие в нём правятся,
                        // а администратор переносит туда загрузки, например в архив
                        $fail(__('load::loads.load_closed'));
                    }
                },
            ],
            'title' => [
                'required',
                'string',
                'min:' . setting('down_title_min'),
                'max:' . setting('down_title_max'),
                // Названия загрузок уникальны — по ним ищут дубликаты релизов
                Rule::unique('downs', 'title')->ignore($this->route('id')),
            ],
            'text'    => ['required', 'string', new TextLengthRule((int) setting('down_text_min'), (int) setting('down_text_max'))],
            'links'   => ['nullable', 'array'],
            'links.*' => ['string', 'min:' . setting('down_link_min'), 'max:' . setting('down_link_max'), 'url'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => __('load::loads.load_not_exist'),
            'category_id.integer'  => __('load::loads.load_not_exist'),
            'title.unique'         => __('load::loads.down_name_exists'),
            // Не сообщение на всё поле: оно перекрыло бы текст антифлуда
            'title.required' => __('validator.text'),
            'title.string'   => __('validator.text'),
            'title.min'      => __('validator.text'),
            'title.max'      => __('validator.text'),
            'text.required'  => __('validator.text'),
            'text.string'    => __('validator.text'),
            'links.*.url'    => __('validator.url'),
            'links.*.*'      => __('validator.text'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                // Загрузка — это файлы или ссылки: хотя бы одно, но не больше лимита
                // Один файл без [] уже получил ошибку правила array, считать его нечего
                $files = $this->file('files');
                $total = $this->attachedFiles() + count($this->input('links', [])) + (is_array($files) ? count($files) : 0);

                if (! $total) {
                    $validator->errors()->add('files', __('validator.file_upload_one'));
                } elseif ($total > setting('maxfiles')) {
                    $validator->errors()->add('files', __('validator.files_max', ['max' => setting('maxfiles')]));
                }
            },
        ];
    }

    public function category(): Load
    {
        return $this->category;
    }

    /**
     * Ссылки без пустых и повторов, null — ссылок нет
     */
    public function links(): ?array
    {
        return $this->validated('links') ?: null;
    }

    /**
     * Раздел, в котором загрузка лежит сейчас
     */
    protected function currentCategoryId(): ?int
    {
        return Down::query()->whereKey($this->route('id'))->value('category_id');
    }

    /**
     * Файлы, уже привязанные к загрузке
     */
    protected function attachedFiles(): int
    {
        $down = Down::query()->find($this->route('id'));

        if (! $down) {
            return 0;
        }

        return File::query()
            ->where('relate_type', Down::$morphName)
            ->where('relate_id', $down->id)
            ->count();
    }

    protected function prepareForValidation(): void
    {
        $links = $this->input('links');

        // Ссылки выключены настройкой — пришедшие из формы или API не сохраняются
        if (! setting('down_allow_links') || ! is_array($links)) {
            $this->merge(['links' => []]);

            return;
        }

        $this->merge(['links' => array_values(array_unique(array_filter($links, static fn ($link) => is_string($link) && $link !== '')))]);
    }
}
