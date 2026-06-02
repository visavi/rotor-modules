<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\Admin\ArticleController as AdminArticleController;
use Modules\Blog\Http\Controllers\Admin\BlogSettingController;
use Modules\Blog\Http\Controllers\ArticleController;

/* Редиректы */
Route::redirect('/blog', '/blogs', 301);
Route::redirect('/blog/tags', '/blogs/tags', 301);
Route::redirect('/blogs/search', '/search', 301);
Route::redirect('/blogs/top', '/articles?sort=rating', 301);
Route::redirect('/articles/comments/{id}', '/articles/{id}', 301);
Route::redirect('/articles/comment/{id}/{cid}', '/articles/{id}?cid={cid}', 301);
Route::redirect('/articles/rss/{id}', '/articles/{id}', 301);
Route::redirect('/articles/{id}/rss', '/articles/{id}', 301);
Route::redirect('/articles/print/{id}', '/articles/{id}', 301);
Route::redirect('/articles/{id}/print', '/articles/{id}', 301);
Route::redirect('/articles/end/{id}', '/articles/{id}', 301);
Route::redirect('/articles/{id}/comments', '/articles/{id}', 301);
Route::get('/blogs/active/articles', static fn () => redirect('/articles/active/articles?' . request()->server('QUERY_STRING'), 301));
Route::get('/blogs/active/comments', static fn () => redirect('/articles/active/comments?' . request()->server('QUERY_STRING'), 301));

/* Категория блогов */
Route::middleware('web')
    ->controller(ArticleController::class)
    ->prefix('blogs')
    ->name('blogs.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'blog')->name('blog');
        Route::get('/tags', 'tags')->name('tags');
        Route::get('/tags-search', 'searchTags')->name('tags-search');
        Route::get('/tags/{tag}', 'getTag')->where('tag', '.+')->name('tag');
        Route::get('/authors', 'authors')->name('authors');
        Route::get('/rss', 'rss')->name('rss');
        Route::match(['get', 'post'], '/create', 'create')->name('create');
        Route::get('/main', 'main')->name('main');
    });

/* Статьи блогов */
Route::middleware('web')
    ->controller(ArticleController::class)
    ->prefix('articles')
    ->name('articles.')
    ->group(function () {
        Route::get('/', 'newArticles')->name('index');
        Route::get('/{slug}', 'view')->name('view');
        Route::post('/{id}/comments', 'storeComment')->name('add-comment');
        Route::get('/new/comments', 'newComments')->name('new-comments');
        Route::get('/active/articles', 'userArticles')->name('user-articles');
        Route::get('/active/comments', 'userComments')->name('user-comments');
        Route::match(['get', 'post'], '/{id}/edit', 'edit')->name('edit');
    });

/* Админ — блоги и статьи */
Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        Route::controller(AdminArticleController::class)
            ->prefix('blogs')
            ->name('admin.blogs.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{id}', 'blog')->name('blog');
                Route::post('/create', 'create')->name('create');
                Route::match(['get', 'post'], '/{id}/edit', 'edit')->name('edit');
                Route::delete('/{id}/delete', 'delete')->name('delete');
                Route::post('/restatement', 'restatement')->name('restatement');
            });

        Route::controller(AdminArticleController::class)
            ->prefix('articles')
            ->name('admin.articles.')
            ->group(function () {
                Route::match(['get', 'post'], '/{id}/edit', 'editArticle')->name('edit');
                Route::delete('/{id}/delete', 'deleteArticle')->name('delete');
                Route::post('/{id}/publish', 'publish')->name('publish');
                Route::get('/new', 'new')->name('new');
            });

        Route::controller(BlogSettingController::class)
            ->name('blog.')
            ->group(function () {
                Route::get('/blog-settings', 'index')->name('settings');
                Route::post('/blog-settings', 'update')->name('settings.update');
            });
    });
