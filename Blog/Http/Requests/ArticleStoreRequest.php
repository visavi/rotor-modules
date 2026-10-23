<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Requests;

use App\Http\Requests\CommentRequest;
use App\Services\FileService;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Blog\Models\Article;

/**
 * Новая статья — общая для сайта и API
 */
class ArticleStoreRequest extends ArticleRequest
{
    /**
     * Отказ до проверки полей: исправлять форму, которую всё равно не примут, незачем
     */
    public function authorize(): Response
    {
        return Gate::inspect('create', Article::class);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['text'][] = CommentRequest::floodRule();

        return $rules + FileService::rules(Article::$morphName);
    }
}
