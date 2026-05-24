<?php

use App\Classes\Hook;
use App\Classes\Registry;
use App\Classes\Restatement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Forum\Models\Bookmark;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;

Registry::complaint(Post::$morphName, function (int $id, mixed $page): array {
    $model = Post::query()->find($id);
    $path = $model ? route('topics.topic', ['id' => $model->topic_id, 'pid' => $model->id], false) : null;

    return ['model' => $model, 'path' => $path];
});

Registry::onDeleteUser(function (User $user): void {
    Bookmark::query()->where('user_id', $user->id)->delete();
});

Registry::sitemap('topics', function (): array {
    return Cache::remember('TopicsSitemap', 600, static function () {
        $topics = Topic::query()
            ->orderByDesc('created_at')
            ->limit(10000)
            ->get();

        $locs = [];
        foreach ($topics as $topic) {
            $locs[] = [
                'loc'     => route('topics.topic', ['id' => $topic->id]),
                'lastmod' => gmdate('c', $topic->created_at),
            ];
        }

        return $locs;
    });
});

Registry::pollResolver(Topic::class, function (Topic $topic): ?array {
    if (! $topic->last_post_id) {
        return null;
    }

    return [Post::$morphName, $topic->last_post_id];
});

Registry::onAdminDeleteUser(function (User $user, Request $request): void {
    if ($request->input('deltopics')) {
        $topics = Topic::query()->where('user_id', $user->id)->get();
        $topics->each(static fn (Topic $topic) => $topic->delete());
        if ($topics->isNotEmpty()) {
            Restatement::run('forums');
        }
    }

    if ($request->input('delposts')) {
        $posts = Post::query()->where('user_id', $user->id)->get();
        $posts->each(static fn (Post $post) => $post->delete());
        if ($posts->isNotEmpty()) {
            Restatement::run('forums');
        }
    }
});

// Добавление чекбоксов удаления на страницу удаления пользователя
Hook::add('adminUserDeleteFields', static fn () => '<div class="form-check">
        <input type="checkbox" class="form-check-input" value="1" name="deltopics" id="deltopics">
        <label class="form-check-label" for="deltopics">' . __('users.forum_topics') . '</label>
    </div>
    <div class="form-check">
        <input type="checkbox" class="form-check-input" value="1" name="delposts" id="delposts">
        <label class="form-check-label" for="delposts">' . __('users.forum_posts') . '</label>
    </div>');

// Ссылки на форум в анкете пользователя
Hook::add('userProfileLinks', static function ($user) {
    return '<li class="list-inline-item"><b><a href="' . route('forums.active-topics', ['user' => $user->login]) . '">' . __('forum::forums.forums') . '</a></b>'
        . ' (<a href="' . route('forums.active-posts', ['user' => $user->login]) . '">' . __('main.messages') . '</a>)</li>';
});

// Ссылка в боковом меню
Hook::add('sidebarMenu', static function () {
    $url = route('forums.index');
    $active = request()->is('forums*', 'topics*') ? ' active' : '';
    $label = __('forum::forums.forums');
    $stats = statsForum();

    return '<li>
        <a class="menu-item' . $active . '" href="' . $url . '">
            <i class="menu-icon far fa-comment-alt"></i>
            <span class="menu-label">' . $label . '</span>
            <span class="badge menu-badge">' . $stats . '</span>
        </a>
    </li>';
}, 5);

// Блок форума в панели администратора
Hook::add('adminBlockEditor', static function () {
    $url = route('admin.forums.index');
    $label = __('forum::forums.forums');
    $stats = statsForum();

    return '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span><br>';
});

// Ссылка на настройки в навигации настроек
Hook::add('adminSettingsNav', static function () {
    return '<a class="nav-link" href="' . route('forum.settings') . '">' . __('forum::forums.settings') . '</a>';
});
