<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HandlesApiComments;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\News\Http\Resources\NewsResource;
use Modules\News\Models\News;

class NewsApiController extends Controller
{
    use HandlesApiComments;

    /**
     * Список новостей
     */
    public function index(Request $request): JsonResource
    {
        $news = News::query()
            ->select('news.*', 'polls.vote')
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('news.id', 'polls.relate_id')
                    ->where('polls.relate_type', News::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->orderBy('created_at', $this->apiOrder($request, 'desc'))
            ->with('user', 'files')
            ->paginate($this->apiPerPage($request));

        return NewsResource::collection($news);
    }

    /**
     * Новость с комментариями
     */
    public function view(int $id, Request $request): JsonResource
    {
        $news = News::query()
            ->select('news.*', 'polls.vote')
            ->where('news.id', $id)
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('news.id', 'polls.relate_id')
                    ->where('polls.relate_type', News::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->with('user', 'files')
            ->first();

        if (! $news) {
            abort(404, __('main.record_not_found'));
        }

        // Запись — в data, её комментарии страницей — в comments
        return NewsResource::make($news)
            ->additional(['comments' => $this->apiCommentsBlock($news, $request)]);
    }
}
