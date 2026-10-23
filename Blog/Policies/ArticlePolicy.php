<?php

declare(strict_types=1);

namespace Modules\Blog\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Blog\Models\Article;

/**
 * Права на статьи на сайте и в API: администрация правит чужое в админке
 */
class ArticlePolicy
{
    /**
     * Публикация может быть закрыта настройкой — кроме администрации
     */
    public function create(User $user): Response
    {
        return $user->isAdmin() || setting('blog_create')
            ? Response::allow()
            : Response::deny(__('blog::blogs.articles_closed'));
    }

    public function update(User $user, Article $article): Response
    {
        return $article->user_id === $user->id
            ? Response::allow()
            : Response::deny(__('blog::blogs.article_not_author'));
    }
}
