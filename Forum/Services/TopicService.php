<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use App\Models\User;
use App\Services\FileService;
use Illuminate\Http\UploadedFile;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;
use Modules\Forum\Models\Vote;
use Modules\Forum\Models\VoteAnswer;

/**
 * Создание темы — общее для сайта и API
 */
class TopicService
{
    public function __construct(private readonly FileService $files)
    {
    }

    /**
     * Создаёт тему с первым сообщением, вложениями и опросом
     *
     * @param list<string>             $answers  варианты опроса, пусто — без опроса
     * @param array<int, UploadedFile> $uploaded файлы из запроса
     */
    public function create(User $user, Forum $forum, string $title, string $text, ?string $question, array $answers, array $uploaded = []): Topic
    {
        $topic = Topic::query()->create([
            'forum_id'   => $forum->id,
            'title'      => $title,
            'user_id'    => $user->id,
            'updated_at' => now(),
        ]);

        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'user_id'  => $user->id,
            'text'     => $text,
            'ip'       => getIp(),
            'brow'     => getBrowser(),
        ]);

        $this->files->attach($post, $uploaded);

        if ($question && $answers) {
            $vote = Vote::query()->create([
                'title'    => $question,
                'topic_id' => $topic->id,
            ]);

            VoteAnswer::query()->insert(array_map(
                static fn (string $answer) => ['vote_id' => $vote->id, 'answer' => $answer],
                $answers,
            ));
        }

        clearCache(['statForums', 'recentTopics']);

        return $topic;
    }
}
