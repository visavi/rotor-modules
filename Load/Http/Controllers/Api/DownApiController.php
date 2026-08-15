<?php

declare(strict_types=1);

namespace Modules\Load\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\User;
use App\Traits\HandlesApiComments;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Load\Http\Resources\DownResource;
use Modules\Load\Http\Resources\LoadResource;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;

class DownApiController extends Controller
{
    use HandlesApiComments;

    /**
     * Категории загрузок с подкатегориями
     */
    public function categories(): JsonResource
    {
        $categories = Load::query()
            ->where('parent_id', 0)
            ->orderBy('sort')
            ->with('children')
            ->get();

        return LoadResource::collection($categories);
    }

    /**
     * Список загрузок, при указании category_id — загрузки категории
     */
    public function index(Request $request): JsonResource
    {
        $categoryId = $request->integer('category_id');

        // Сортировка та же, что на сайте: date, name, loads, rating, comments
        [, $orderBy] = Down::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));

        $downs = Down::query()
            ->active()
            ->when($categoryId, static fn ($query) => $query->where('category_id', $categoryId))
            ->select('downs.*', 'polls.vote')
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('downs.id', 'polls.relate_id')
                    ->where('polls.relate_type', Down::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->orderBy(...$orderBy)
            ->with('user', 'category.parent', 'files')
            ->paginate($this->apiPerPage($request));

        return DownResource::collection($downs);
    }

    /**
     * Загрузка с комментариями
     */
    public function view(int $id, Request $request): JsonResource
    {
        $down = Down::query()
            ->select('downs.*', 'polls.vote')
            ->where('downs.id', $id)
            ->leftJoin('polls', static function (JoinClause $join) {
                $join->on('downs.id', 'polls.relate_id')
                    ->where('polls.relate_type', Down::$morphName)
                    ->where('polls.user_id', getUser('id'));
            })
            ->with('user', 'category.parent', 'files')
            ->first();

        if (! $down) {
            abort(404, __('load::loads.down_not_exist'));
        }

        // Непроверенная загрузка доступна только автору и администрации
        if (! $down->active && ! isAdmin(User::ADMIN) && getUser('id') !== $down->user_id) {
            abort(403, __('load::loads.down_not_verified'));
        }

        // Комментарии страницей, сама загрузка — в additional
        return CommentResource::collection($this->apiComments($down, $request))
            ->additional(['down' => DownResource::make($down)]);
    }
}
