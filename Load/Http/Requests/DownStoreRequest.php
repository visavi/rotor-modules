<?php

declare(strict_types=1);

namespace Modules\Load\Http\Requests;

use App\Http\Requests\CommentRequest;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Load\Models\Down;

/**
 * Новая загрузка — общая для сайта и API
 */
class DownStoreRequest extends DownRequest
{
    /**
     * Отказ до проверки полей: исправлять форму, которую всё равно не примут, незачем
     */
    public function authorize(): Response
    {
        return Gate::inspect('create', Down::class);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['title'][] = CommentRequest::floodRule();

        return $rules + FileService::rules(Down::$morphName);
    }

    /**
     * Файлы, загруженные заранее и ждущие создания записи
     */
    protected function attachedFiles(): int
    {
        return File::query()
            ->pending(Down::$morphName, auth()->id())
            ->count();
    }
}
