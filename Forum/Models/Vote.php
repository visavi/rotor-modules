<?php

declare(strict_types=1);

namespace Modules\Forum\Models;

use App\Models\Poll;
use App\Traits\PollableTrait;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Class Vote
 *
 * @property int             $id
 * @property string          $title
 * @property int             $count
 * @property CarbonImmutable $created_at
 * @property int             $topic_id
 * @property array           $getAnswers
 * @property-read Topic                       $topic
 * @property-read ?Poll                       $poll
 * @property-read Collection<int, VoteAnswer> $answers
 * @property-read Collection<int, Poll>       $polls
 */
class Vote extends Model
{
    use PollableTrait;

    /**
     * The name of the "updated at" column.
     */
    public const ?string UPDATED_AT = null;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Morph name
     */
    public static string $morphName = 'votes';

    /**
     * Запомненный ответ isVoted(): [id пользователя, проголосовал ли]
     *
     * @var array{0: int, 1: bool}|null
     */
    private ?array $votedMemo = null;

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Возвращает топик
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id')->withDefault();
    }

    /**
     * Возвращает варианты ответов
     *
     * @return HasMany<VoteAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(VoteAnswer::class, 'vote_id')
            ->orderBy('id');
    }

    /**
     * Результаты голосования, отсортированные по убыванию
     *
     * Проценты считаются здесь, а не в контроллере, чтобы шаблон голосования
     * можно было выводить и в теме, и в ленте
     *
     * @return array<int, array{answer: string, result: int, percent: float, width: int}>
     */
    public function results(): array
    {
        if ($this->answers->isEmpty()) {
            return [];
        }

        $results = Arr::pluck($this->answers, 'result', 'answer');
        $max = max($results);
        $max = $max > 0 ? $max : 1;
        $sum = $this->count > 0 ? $this->count : 1;

        arsort($results);

        $items = [];
        foreach ($results as $answer => $result) {
            $items[] = [
                'answer'  => (string) $answer,
                'result'  => (int) $result,
                'percent' => round(($result * 100) / $sum, 1),
                'width'   => (int) round(($result * 100) / $max),
            ];
        }

        return $items;
    }

    /**
     * Проголосовал ли текущий пользователь (гостю показываются результаты)
     *
     * Голос проверяется запросом, а не связью poll: модели ленты лежат в общем
     * кеше, и загруженная связь досталась бы вместе с ними следующему пользователю.
     * Ответ запоминается вместе с id пользователя — по той же причине: кешированную
     * модель следующим запросом читает уже другой посетитель
     */
    public function isVoted(): bool
    {
        $userId = getUser('id');

        if (! $userId) {
            return true;
        }

        if ($this->votedMemo && $this->votedMemo[0] === $userId) {
            return $this->votedMemo[1];
        }

        $voted = $this->polls()->where('user_id', $userId)->exists();

        $this->votedMemo = [$userId, $voted];

        return $voted;
    }

    /**
     * Удаление голосования
     */
    public function delete(): ?bool
    {
        return DB::transaction(function () {
            $this->polls()->delete();

            $this->answers->each(function (VoteAnswer $answer) {
                $answer->delete();
            });

            return parent::delete();
        });
    }
}
