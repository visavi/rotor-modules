<?php

declare(strict_types=1);

namespace Modules\Photo\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Traits\HandlesApiComments;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Photo\Http\Resources\PhotoResource;
use Modules\Photo\Models\Photo;

class PhotoApiController extends Controller
{
    use HandlesApiComments;

    /**
     * Список фото
     */
    public function index(Request $request): JsonResource
    {
        // Сортировка та же, что на сайте: date, rating, comments
        [, $orderBy] = Photo::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));

        $photos = Photo::query()
            ->select('photos.*', 'polls.vote')
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('photos.id', 'polls.relate_id')
                    ->where('polls.relate_type', Photo::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->orderBy(...$orderBy)
            ->with('user', 'files')
            ->paginate($this->apiPerPage($request));

        return PhotoResource::collection($photos);
    }

    /**
     * Фото с комментариями
     */
    public function view(int $id, Request $request): JsonResource
    {
        $photo = Photo::query()
            ->select('photos.*', 'polls.vote')
            ->where('photos.id', $id)
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('photos.id', 'polls.relate_id')
                    ->where('polls.relate_type', Photo::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->with('user', 'files')
            ->first();

        if (! $photo) {
            abort(404, __('photo::photos.photo_not_exist'));
        }

        // Комментарии страницей, само фото — в additional
        return CommentResource::collection($this->apiComments($photo, $request))
            ->additional(['photo' => PhotoResource::make($photo)]);
    }
}
