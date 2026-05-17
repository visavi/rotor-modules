<?php

namespace Modules\Blog\Observers;

use Modules\Blog\Models\Article;
use Modules\Blog\Models\Blog;

class ArticleObserver
{
    public function created(Article $article): void
    {
        $article->category->restatement();
        clearCache(['statArticles', 'recentArticles']);
    }

    public function updated(Article $article): void
    {
        if ($article->isDirty('category_id')) {
            $oldCategoryId = $article->getOriginal('category_id');
            $newCategoryId = $article->category_id;

            if ($oldCategoryId !== $newCategoryId) {
                $oldCategory = Blog::query()->find($oldCategoryId);
                $oldCategory?->restatement();
            }

            $article->category->restatement();
        }

        if ($article->wasChanged('active')) {
            $user = $article->user;
            $pointAmount = setting('blog_point');
            $moneyAmount = setting('blog_money');

            if ($article->active) {
                $user->increment('point', $pointAmount);
                $user->increment('money', $moneyAmount);
            } else {
                $user->decrement('point', min($pointAmount, $user->point));
                $user->decrement('money', min($moneyAmount, $user->money));
            }
        }

        clearCache(['statArticles', 'recentArticles']);
    }

    public function deleted(Article $article): void
    {
        $article->category->restatement();
        clearCache(['statArticles', 'recentArticles']);
    }

    public function restored(Article $article): void {}

    public function forceDeleted(Article $article): void {}
}
