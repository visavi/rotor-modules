<?php

declare(strict_types=1);

namespace Modules\Board\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HandlesApiPagination;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Board\Http\Resources\BoardResource;
use Modules\Board\Http\Resources\ItemResource;
use Modules\Board\Models\Board;
use Modules\Board\Models\Item;

class ItemApiController extends Controller
{
    use HandlesApiPagination;

    /**
     * Категории объявлений с подкатегориями
     */
    public function categories(): JsonResource
    {
        $categories = Board::query()
            ->where('parent_id', 0)
            ->orderBy('sort')
            ->with('children')
            ->get();

        return BoardResource::collection($categories);
    }

    /**
     * Список объявлений, при указании category_id — объявления категории
     */
    public function index(Request $request): JsonResource
    {
        $categoryId = $request->integer('category_id');

        // Сортировка та же, что на сайте: date, title, price
        [, $orderBy] = Item::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));

        $items = Item::query()
            ->when($categoryId, static fn ($query) => $query->where('board_id', $categoryId))
            // Истёкшие объявления в списках не показываются
            ->where('expires_at', '>', now())
            ->orderBy(...$orderBy)
            ->with('user', 'category.parent', 'files')
            ->paginate($this->apiPerPage($request));

        return ItemResource::collection($items);
    }

    /**
     * Объявление
     *
     * Комментариев у объявлений нет, поэтому запись отдаётся одна, без страницы
     */
    public function view(int $id): JsonResource
    {
        $item = Item::query()
            ->with('user', 'category.parent', 'files')
            ->find($id);

        if (! $item) {
            abort(404, __('board::boards.item_not_exist'));
        }

        // Истёкшее объявление остаётся доступным автору, как и на сайте
        if ($item->expires_at->lte(now()) && getUser() && getUser('id') !== $item->user_id) {
            abort(403, __('board::boards.item_not_active'));
        }

        return ItemResource::make($item);
    }
}
