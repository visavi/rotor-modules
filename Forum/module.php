<?php

use Illuminate\Support\Facades\DB;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;
use Modules\Forum\Models\Vote;
use Modules\Forum\Observers\PostObserver;
use Modules\Forum\Observers\TopicObserver;

return [
    'name'        => 'Форум',
    'description' => 'Форум с темами и сообщениями',
    'version'     => '1.0.2',
    'requires'    => '14.0.1',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Topic::class => [
            'label'  => __('forum::forums.topics'),
            'search' => ['view' => 'forum::search/_topics', 'with' => ['forum', 'lastPost']],
            'feed'   => [
                'with'  => ['lastPost.user', 'lastPost.files', 'forum.parent'],
                'view'  => 'forum::feeds/_topics',
                'scope' => fn ($query) => $query->leftJoin('posts', 'topics.last_post_id', '=', 'posts.id'),
                // В ленте голосование идёт за последний пост темы, а не за саму тему
                'poll' => fn (Topic $topic): ?array => $topic->last_post_id
                    ? [Post::$morphName, $topic->last_post_id]
                    : null,
            ],
        ],
        Post::class => [
            'label'  => __('forum::forums.forum_posts'),
            'search' => ['view' => 'forum::search/_posts', 'with' => ['topic']],
            'upload' => 'file',
            'rating' => true,
            'spam'   => true,
        ],
        Vote::class => [],
    ],

    'observers' => [
        Topic::class => TopicObserver::class,
        Post::class  => PostObserver::class,
    ],

    'actions' => [
        '/admin/forums'         => __('forum::forums.forums'),
        '/admin/forum-settings' => __('forum::forums.settings'),
    ],

    'restatement' => [
        'forums' => function () {
            DB::update('update topics set count_posts = (select count(*) from posts where topics.id = posts.topic_id)');
            DB::update('update forums set count_topics = (select count(*) from topics where forums.id = topics.forum_id)');
            DB::update('update forums set count_posts = (select coalesce(sum(count_posts), 0) from topics where forums.id = topics.forum_id)');
        },
        'votes' => function () {
            DB::update('update votes set count = (select coalesce(sum(result), 0) from voteanswer where votes.id = voteanswer.vote_id)');
        },
    ],
];
