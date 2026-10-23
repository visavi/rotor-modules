<?php

declare(strict_types=1);

namespace Modules\Load\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Models\User;
use App\Traits\HandlesApiComments;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Load\Http\Requests\DownStoreRequest;
use Modules\Load\Http\Requests\OwnDownRequest;
use Modules\Load\Http\Resources\DownResource;
use Modules\Load\Http\Resources\LoadResource;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;
use Modules\Load\Services\DownService;

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
        $user = $this->apiUser($request);

        // Сортировка та же, что на сайте: date, name, loads, rating, comments
        [, $orderBy] = Down::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));

        $downs = Down::query()
            ->active()
            ->when($categoryId, static fn ($query) => $query->where('category_id', $categoryId))
            ->when($user, static fn ($query, User $user) => $query->where('downs.user_id', $user->id))
            ->withUserVote()
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
            ->withUserVote()
            ->where('downs.id', $id)
            ->with('user', 'category.parent', 'files')
            ->firstOrFail();

        $this->authorize('view', $down);

        // Запись — в data, её комментарии страницей — в comments
        return DownResource::make($down)
            ->additional(['comments' => $this->apiCommentsBlock($down, $request)]);
    }

    /**
     * Создание загрузки
     */
    public function store(DownStoreRequest $request, Flood $flood, DownService $service, #[CurrentUser] User $user): JsonResponse
    {
        $down = $service->create($user, $request, $request->file('files', []));

        $flood->saveState();

        $down->load('user', 'category', 'files');

        return response()->json([
            'message' => __('load::loads.down_added_success'),
            'down'    => DownResource::make($down),
        ], 201);
    }

    /**
     * Редактирование своей непроверенной загрузки
     */
    public function update(int $id, OwnDownRequest $request): JsonResponse
    {
        $down = $request->down();

        $down->update([
            'category_id' => $request->category()->id,
            'title'       => $request->string('title')->value(),
            'text'        => $request->string('text')->value(),
            'links'       => $request->links(),
        ]);

        $down->load('user', 'category', 'files');

        return response()->json([
            'message' => __('load::loads.down_edited_success'),
            'down'    => DownResource::make($down),
        ]);
    }
}
