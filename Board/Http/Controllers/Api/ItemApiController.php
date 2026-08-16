<?php

declare(strict_types=1);

namespace Modules\Board\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Services\FileService;
use App\Traits\HandlesApiPagination;
use Closure;
use Illuminate\Http\JsonResponse;
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

    /**
     * Создание объявления
     */
    public function store(Request $request, Flood $flood, FileService $files): JsonResponse
    {
        if (! isAdmin() && ! setting('boards_create')) {
            abort(422, __('board::boards.boards_closed'));
        }

        $user = getUser();

        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'min:1'],
            'title'       => ['required', 'string', 'min:' . setting('board_title_min'), 'max:' . setting('board_title_max')],
            'text'        => [
                'required',
                'string',
                'min:' . setting('board_text_min'),
                'max:' . setting('board_text_max'),
                static function (string $attribute, mixed $value, Closure $fail) use ($flood) {
                    if ($flood->isFlood()) {
                        $fail(__('validator.flood', ['sec' => $flood->getPeriod()]));
                    }
                },
            ],
            'price' => ['nullable', 'integer', 'min:0'],
            'phone' => ['nullable', 'string', 'max:20'],
        ] + FileService::rules(Item::$morphName));

        $board = $this->findOpenCategory((int) $validated['category_id']);

        $item = Item::query()->create([
            'board_id'   => $board->id,
            'title'      => antimat($validated['title']),
            'text'       => antimat($validated['text']),
            'user_id'    => $user->id,
            'price'      => (int) ($validated['price'] ?? 0),
            'phone'      => $this->normalizePhone($validated['phone'] ?? null),
            'created_at' => now(),
            'updated_at' => now(),
            // Объявление живёт ограниченный срок и потом пропадает из списков
            'expires_at' => now()->addDays((int) setting('boards_period')),
        ]);

        $item->category->increment('count_items');

        // Медиа можно приложить к запросу или загрузить заранее — с relate_id = 0
        $files->attachUploaded($item, $request->file('files', []));
        $files->attachPending($item);

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
    public function update(int $id, Request $request): JsonResponse
    {
        $item = $this->findOwnItem($id);

        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'min:1'],
            'title'       => ['required', 'string', 'min:' . setting('board_title_min'), 'max:' . setting('board_title_max')],
            'text'        => ['required', 'string', 'min:' . setting('board_text_min'), 'max:' . setting('board_text_max')],
            'price'       => ['nullable', 'integer', 'min:0'],
            'phone'       => ['nullable', 'string', 'max:20'],
        ]);

        $board = $this->findOpenCategory((int) $validated['category_id']);

        // Счётчики категорий переезжают вместе с объявлением
        if ($item->board_id !== $board->id) {
            $board->increment('count_items');
            Board::query()->where('id', $item->board_id)->decrement('count_items');
        }

        $item->update([
            'board_id' => $board->id,
            'title'    => antimat($validated['title']),
            'text'     => antimat($validated['text']),
            'price'    => (int) ($validated['price'] ?? 0),
            'phone'    => $this->normalizePhone($validated['phone'] ?? null),
        ]);

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
            $item->update(['expires_at' => now()]);
            $item->category->decrement('count_items');

            $message = __('board::boards.item_success_unpublished');
        } else {
            $period = (int) setting('boards_period');
            // Давно снятое объявление всплывает наверх, свежее сохраняет позицию
            $expired = $item->updated_at->addDays($period)->lte(now());

            $item->update([
                'expires_at' => now()->addDays($period),
                'updated_at' => $expired ? now() : $item->updated_at,
            ]);
            $item->category->increment('count_items');

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
        $item = $this->findOwnItem($id);
        $category = $item->category;

        $item->delete();
        $category->decrement('count_items');

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

    /**
     * Находит открытую категорию объявлений
     */
    private function findOpenCategory(int $id): Board
    {
        $board = Board::query()->find($id);

        if (! $board) {
            abort(422, __('board::boards.category_not_exist'));
        }

        if ($board->closed) {
            abort(422, __('board::boards.category_closed'));
        }

        return $board;
    }

    /**
     * Оставляет в телефоне только цифры и плюс
     */
    private function normalizePhone(?string $phone): string
    {
        return preg_replace('/[^\d+]/', '', (string) $phone);
    }
}
