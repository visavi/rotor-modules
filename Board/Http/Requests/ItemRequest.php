<?php

declare(strict_types=1);

namespace Modules\Board\Http\Requests;

use App\Rules\PhoneRule;
use App\Rules\TextLengthRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Board\Models\Board;
use Modules\Board\Models\Item;

/**
 * Объявление — правка на сайте, в админке и в API
 */
class ItemRequest extends FormRequest
{
    private ?Board $category = null;

    public function rules(): array
    {
        return [
            'category_id' => [
                'bail',
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $this->category = Board::query()->find($value);

                    if (! $this->category) {
                        $fail(__('board::boards.category_not_exist'));
                    } elseif ($this->category->closed) {
                        $fail(__('board::boards.category_closed'));
                    }
                },
            ],
            'title' => ['required', 'string', 'min:' . setting('board_title_min'), 'max:' . setting('board_title_max')],
            'text'  => ['required', 'string', new TextLengthRule((int) setting('board_text_min'), (int) setting('board_text_max'))],
            'price' => ['nullable', 'integer', 'min:0'],
            'phone' => ['nullable', 'string', new PhoneRule()],
            'city'  => ['nullable', 'string', 'min:2', 'max:50'],
            // Ключи Item::MESSENGERS
            'messengers'   => ['nullable', 'array'],
            'messengers.*' => ['string', Rule::in(array_keys(Item::MESSENGERS))],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => __('board::boards.category_not_exist'),
            'category_id.integer'  => __('board::boards.category_not_exist'),
            'title.required'       => __('validator.text'),
            'title.string'         => __('validator.text'),
            'text.required'        => __('validator.text'),
            'text.string'          => __('validator.text'),
        ];
    }

    /**
     * Категория, найденная при проверке
     */
    public function category(): Board
    {
        return $this->category;
    }

    /**
     * Поля объявления из запроса
     */
    public function itemFields(): array
    {
        return [
            'board_id'   => $this->category->id,
            'title'      => $this->string('title')->value(),
            'text'       => $this->string('text')->value(),
            'price'      => $this->integer('price'),
            'phone'      => $this->string('phone')->value(),
            'city'       => $this->input('city'),
            'messengers' => $this->input('messengers', []),
        ];
    }

    protected function prepareForValidation(): void
    {
        // Маска ввода добавляет пробелы, скобки и дефисы — в базе только цифры и плюс
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => preg_replace('/[^\d+]/', '', $this->input('phone')) ?: null]);
        }
    }
}
