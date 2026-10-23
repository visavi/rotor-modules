<?php

declare(strict_types=1);

namespace Modules\Photo\Http\Requests;

use App\Http\Requests\CommentRequest;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Modules\Photo\Models\Photo;

/**
 * Новое фото — общее для сайта и API
 */
class PhotoStoreRequest extends PhotoRequest
{
    /**
     * Отказ до проверки полей: исправлять форму, которую всё равно не примут, незачем
     */
    public function authorize(): Response
    {
        return Gate::inspect('create', Photo::class);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        // Антифлуд на заголовке: описание необязательно, на пустом поле правило не запустится
        $rules['title'][] = CommentRequest::floodRule();

        return $rules + FileService::rules(Photo::$morphName);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                // Снимок обязателен: приложенный к запросу или загруженный заранее
                $pending = File::query()
                    ->pending(Photo::$morphName, auth()->id())
                    ->exists();

                if (! $this->file('files') && ! $pending) {
                    $validator->errors()->add('files', __('validator.image_upload_failed'));
                }
            },
        ];
    }
}
