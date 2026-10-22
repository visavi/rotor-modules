<?php

declare(strict_types=1);

namespace Modules\Board\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Services\FileService;
use App\Traits\HandlesApiPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Board\Http\Requests\ItemRequest;
use Modules\Board\Http\Requests\ItemStoreRequest;
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
        $city = $request->string('city')->trim()->value();
        $user = $this->apiUser($request);

        // Сортировка та же, что на сайте: date, price, name
        [, $orderBy] = Item::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));

        $items = Item::query()
            ->when($categoryId, static fn ($query) => $query->where('board_id', $categoryId))
            ->when($city !== '', static fn ($query) => $query->where('city', $city))
            ->when($user, static fn ($query) => $query->where('user_id', $user->id))
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

        // Истёкшее объявление открыто всем, признак — в поле expired
        return ItemResource::make($item);
    }

    /**
     * Создание объявления
     */
    public function store(ItemStoreRequest $request, Flood $flood, FileService $files): JsonResponse
    {
        if (! isAdmin() && ! setting('boards_create')) {
            abort(422, __('board::boards.boards_closed'));
        }

        $item = Item::query()->create($request->itemFields() + [
            'user_id'    => getUser('id'),
            'created_at' => now(),
            'updated_at' => now(),
            // Объявление живёт ограниченный срок и потом пропадает из списков
            'expires_at' => now()->addDays((int) setting('boards_period')),
        ]);

        $item->category->increment('count_items');

        // Медиа можно приложить к запросу или загрузить заранее — с relate_id = 0
        $files->attach($item, $request->file('files', []));

        clearCache(['statBoards', 'recentBoards']);
        $flood->saveState();

        $item->load('user', 'category', 'files');

        return response()->json([
            'message' => __('board::boards.item_success_added'),
            'item'    => ItemResource::make($item),
        ], 201);
    }

    /**
     * Редактирование своего объявления
     */
    public function update(int $id, ItemRequest $request): JsonResponse
    {
        $item = $this->findOwnItem($id);

        $item->moveTo($request->category());
        $item->update($request->itemFields());

        clearCache(['statBoards', 'recentBoards']);

        $item->load('user', 'category', 'files');

        return response()->json([
            'message' => __('board::boards.item_success_edited'),
            'item'    => ItemResource::make($item),
        ]);
    }

    /**
     * Снятие с публикации и повторная публикация
     */
    public function publish(int $id): JsonResponse
    {
        $item = $this->findOwnItem($id);

        if ($item->expires_at->gt(now())) {
            $item->unpublish();
            $message = __('board::boards.item_success_unpublished');
        } else {
            $item->publish();
            $message = __('board::boards.item_success_published');
        }

        $item->load('user', 'category', 'files');

        return response()->json([
            'message' => $message,
            'item'    => ItemResource::make($item),
        ]);
    }

    /**
     * Удаление своего объявления
     */
    public function destroy(int $id): JsonResponse
    {
        $this->findOwnItem($id)->delete();

        clearCache(['statBoards', 'recentBoards']);

        return response()->json(['message' => __('board::boards.item_success_deleted')]);
    }

    /**
     * Находит своё объявление
     */
    private function findOwnItem(int $id): Item
    {
        $item = Item::query()->find($id);

        if (! $item) {
            abort(404, __('board::boards.item_not_exist'));
        }

        if ($item->user_id !== getUser('id')) {
            abort(403, __('board::boards.item_not_author'));
        }

        return $item;
    }
}
