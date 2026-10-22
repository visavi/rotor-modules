<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Requests;

use App\Http\Requests\CommentRequest;
use App\Services\FileService;
use Illuminate\Auth\Access\Response;
use Modules\Blog\Models\Article;

/**
 * Новая статья — общая для сайта и API
 */
class ArticleStoreRequest extends ArticleRequest
{
    /**
     * Приём статей может быть закрыт настройкой — кроме администрации.
     * Отказ до проверки полей: исправлять форму, которую всё равно не примут, незачем
     */
    public function authorize(): Response
    {
        return isAdmin() || setting('blog_create') ? Response::allow() : Response::deny(__('blog::blogs.articles_closed'));
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['text'][] = CommentRequest::floodRule();

        return $rules + FileService::rules(Article::$morphName);
    }
}
