<?php

declare(strict_types=1);

namespace Modules\Board\Http\Requests;

use App\Http\Requests\CommentRequest;
use App\Services\FileService;
use Modules\Board\Models\Item;

/**
 * Новое объявление — общее для сайта и API
 */
class ItemStoreRequest extends ItemRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['text'][] = CommentRequest::floodRule();

        return $rules + FileService::rules(Item::$morphName);
    }
}
