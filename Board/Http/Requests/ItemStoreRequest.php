<?php

declare(strict_types=1);

namespace Modules\Board\Http\Requests;

use App\Http\Requests\CommentRequest;
use App\Services\FileService;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Board\Models\Item;

/**
 * Новое объявление — общее для сайта и API
 */
class ItemStoreRequest extends ItemRequest
{
    /**
     * Отказ до проверки полей: исправлять форму, которую всё равно не примут, незачем
     */
    public function authorize(): Response
    {
        return Gate::inspect('create', Item::class);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['text'][] = CommentRequest::floodRule();

        return $rules + FileService::rules(Item::$morphName);
    }
}
