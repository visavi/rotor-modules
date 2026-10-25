<?php

declare(strict_types=1);

namespace Modules\HomeBuilder\Http\Requests;

use App\Rules\TextLengthRule;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HomeBuilder\Models\Block;

/**
 * Блок главной: добавление и правка
 *
 * Поля зависят от типа блока. Тип берётся из правимого блока или из ?type=
 * у нового — его права проверяются до полей, как и на открытии формы
 */
class BlockRequest extends FormRequest
{
    private Block $block;

    public function rules(): array
    {
        $rules = [
            'title'      => ['nullable', 'string', 'max:100'],
            'width'      => ['required', Rule::in(Block::WIDTHS)],
            'visibility' => ['required', Rule::in([Block::VISIBLE_ALL, Block::VISIBLE_GUESTS, Block::VISIBLE_USERS])],
            'starts_at'  => ['nullable', 'date_format:Y-m-d\TH:i'],
            'ends_at'    => ['nullable', 'date_format:Y-m-d\TH:i', Rule::when($this->filled('starts_at'), 'after:starts_at')],
        ];

        return $rules + match ($this->block->type) {
            Block::TEXT => [
                'text' => ['required', 'string', new TextLengthRule(1, 10000)],
            ],
            // Код выводится как есть, поэтому и длина — вместе с тегами
            Block::HTML => [
                'code' => ['required', 'string', 'max:65000'],
            ],
            Block::ENTRIES => [
                'sources' => [
                    'bail',
                    'required',
                    'array',
                    'max:50',
                    static function (string $attribute, mixed $value, Closure $fail): void {
                        // Элемент проверяется сам: вложенный массив в array_diff дал бы 500
                        foreach ($value as $source) {
                            if (! is_string($source) || ! array_key_exists($source, Block::sourceList())) {
                                $fail(__('home_builder::home_builder.sources_invalid'));

                                return;
                            }
                        }
                    },
                ],
                'layout'        => ['required', Rule::in(array_keys(Block::layoutOptions()))],
                'items'         => ['required', 'integer', 'between:1,50'],
                'cache_minutes' => ['required', 'integer', 'between:0,1440'],
            ],
            // Выбранный файл либо новый с примером внутри
            Block::TEMPLATE => [
                'template_name' => [
                    'bail',
                    'nullable',
                    'regex:/^[a-z0-9][a-z0-9\-]{0,49}$/',
                    static function (string $attribute, mixed $value, Closure $fail): void {
                        if (file_exists(Block::templatePath() . '/' . $value . '.blade.php')) {
                            $fail(__('home_builder::home_builder.template_exists'));
                        }
                    },
                ],
                // Форма шлёт template_name всегда, пустым тоже — exclude_with по наличию ключа не годится
                'template' => [Rule::excludeIf($this->filled('template_name')), 'required', Rule::in(Block::templates())],
            ],
            default => [],
        };
    }

    public function messages(): array
    {
        return [
            'title'               => __('validator.text'),
            'width'               => __('home_builder::home_builder.width_invalid'),
            'visibility'          => __('home_builder::home_builder.visibility_invalid'),
            'starts_at'           => __('home_builder::home_builder.date_invalid'),
            'ends_at.after'       => __('home_builder::home_builder.ends_at_invalid'),
            'ends_at'             => __('home_builder::home_builder.date_invalid'),
            'text'                => __('validator.text'),
            'code'                => __('validator.text'),
            'sources'             => __('home_builder::home_builder.sources_invalid'),
            'layout'              => __('home_builder::home_builder.layout_invalid'),
            'items'               => __('home_builder::home_builder.items_invalid'),
            'cache_minutes'       => __('home_builder::home_builder.cache_invalid'),
            'template_name.regex' => __('home_builder::home_builder.template_name_invalid'),
            'template'            => __('home_builder::home_builder.template_invalid'),
        ];
    }

    /**
     * Блок, который правится или добавляется
     */
    public function block(): Block
    {
        return $this->block;
    }

    /**
     * Новый шаблон, который нужно создать перед сохранением блока
     */
    public function newTemplate(): ?string
    {
        return $this->block->type === Block::TEMPLATE ? $this->validated('template_name') : null;
    }

    /**
     * Поля блока из формы
     */
    public function blockFields(): array
    {
        $type = $this->block->type;
        $isEntries = $type === Block::ENTRIES;

        // Свой вид записей приходит как custom:имя — это файл из каталога шаблонов
        $layout = $this->string('layout')->value();
        $layoutTemplate = '';

        if (str_starts_with($layout, Block::CUSTOM_PREFIX)) {
            $layoutTemplate = substr($layout, strlen(Block::CUSTOM_PREFIX));
            $layout = Block::LAYOUT_TEMPLATE;
        }

        return [
            'title'    => $this->string('title')->value(),
            'text'     => $type === Block::TEXT ? $this->string('text')->value() : null,
            'code'     => $type === Block::HTML ? $this->string('code')->value() : null,
            'template' => match ($type) {
                Block::TEMPLATE => $this->newTemplate() ?? $this->validated('template'),
                Block::ENTRIES  => $layoutTemplate,
                default         => '',
            },
            // Ключи из запроса не нужны: в JSON список, а не объект
            'sources'       => $isEntries ? array_values($this->validated('sources')) : null,
            'layout'        => $isEntries ? $layout : '',
            'items'         => $isEntries ? $this->integer('items') : $this->block->items,
            'cache_minutes' => $isEntries ? $this->integer('cache_minutes') : 0,
            'width'         => $this->integer('width'),
            'visibility'    => $this->string('visibility')->value(),
            'active'        => $this->boolean('active'),
            'starts_at'     => $this->localDate('starts_at'),
            'ends_at'       => $this->localDate('ends_at'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('type');

        $this->block = $this->route('id')
            ? Block::findManageable((int) $this->route('id'))
            : Block::draft(is_string($type) ? $type : '');

        // Имя нового шаблона — без расширения, даже если его вписали
        if (is_string($this->input('template_name'))) {
            $name = preg_replace('/\.blade\.php$/i', '', trim($this->input('template_name')));
            $this->merge(['template_name' => $name !== '' ? $name : null]);
        }
    }

    /**
     * Дата из поля datetime-local в часовом поясе админа, пустое поле — без ограничения
     */
    private function localDate(string $key): ?CarbonImmutable
    {
        $value = $this->string($key)->value();

        if ($value === '') {
            return null;
        }

        // Админ вводит своё время, как видит его в списке блоков (dateFixed сдвигает на пояс из анкеты)
        $date = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $value, config('app.timezone')) ?: null;

        return $date?->subHours((int) auth()->user()?->timezone);
    }
}
