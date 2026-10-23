<?php

declare(strict_types=1);

namespace Modules\Board\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Flood;
use App\Models\Reader;
use App\Models\User;
use App\Services\FileService;
use App\Support\CategoryTree;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Board\Http\Requests\ItemRequest;
use Modules\Board\Http\Requests\ItemStoreRequest;
use Modules\Board\Models\Board;
use Modules\Board\Models\Item;

class BoardController extends Controller
{
    /**
     * Главная страница
     */
    public function index(Request $request, ?int $id = null): View
    {
        // Дерево целиком: счётчик категории складывает всю её ветку
        $categories = Board::query()->orderBy('sort')->get();

        CategoryTree::totals($categories, ['count_items' => 'total_items']);
        CategoryTree::nest($categories);

        $board = null;

        if ($id) {
            $board = $categories->firstWhere('id', $id);

            if (! $board) {
                abort(404, __('board::boards.category_not_exist'));
            }
        }

        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'desc');
        $city = $request->string('city')->trim()->value();

        [$sorting, $orderBy] = Item::getSorting($sort, $order);

        $items = Item::query()
            ->when($board, static function (Builder $query) use ($board) {
                return $query->where('board_id', $board->id);
            })
            ->when($city !== '', static fn (Builder $query) => $query->where('city', $city))
            ->where('expires_at', '>', now())
            ->orderBy(...$orderBy)
            ->with('category', 'user', 'files')
            ->paginate(setting('boards_per_page'))
            ->appends(array_filter(compact('sort', 'order', 'city')));

        $boards = $board ? $board->children : $categories->where('parent_id', 0)->values();

        return view('board::boards/index', compact('items', 'board', 'boards', 'sorting', 'city'));
    }

    /**
     * Просмотр объявления
     */
    public function view(int $id): View
    {
        $item = Item::query()
            ->with('category')
            ->find($id);

        if (! $item) {
            abort(404, __('board::boards.item_not_exist'));
        }

        Reader::countingStat($item);

        return view('board::boards/view', compact('item'));
    }

    /**
     * Подсказки городов для формы: из всех объявлений, включая снятые и истёкшие,
     * и из анкет пользователей. Город из анкеты — только если его вписали хотя бы
     * двое: единичные варианты вроде «в деревне» в подсказки объявлений не попадают
     */
    public function cities(Request $request): JsonResponse
    {
        $query = $request->string('query')->trim()->value();

        if (Str::length($query) < 2) {
            return response()->json([]);
        }

        $itemCities = Item::query()
            ->selectRaw('city, count(*) as total')
            ->where('city', 'like', addcslashes($query, '%_\\') . '%')
            ->groupBy('city')
            ->orderByDesc('total')
            ->limit(10)
            ->toBase()
            ->pluck('total', 'city')
            ->all();

        // Один город из двух источников складывается; написание берём из объявлений
        $counts = [];
        foreach ([$itemCities, User::placeCounts('city', $query, 2)] as $source) {
            foreach ($source as $city => $total) {
                $key = mb_strtolower((string) $city);
                $counts[$key] ??= ['city' => (string) $city, 'total' => 0];
                $counts[$key]['total'] += (int) $total;
            }
        }

        $cities = collect($counts)
            ->sortBy([['total', 'desc'], ['city', 'asc']])
            ->take(10)
            ->map(static fn (array $row) => ['value' => $row['city'], 'label' => $row['city']])
            ->values();

        return response()->json($cities);
    }

    /**
     * Телефон объявления по клику
     */
    public function phone(int $id): JsonResponse
    {
        $item = Item::query()->find($id);

        if (! $item?->phone) {
            return response()->json(['success' => false, 'message' => __('board::boards.item_not_exist')]);
        }

        return response()->json([
            'success' => true,
            'html'    => view('board::boards/_phone', ['item' => $item, 'revealed' => true])->render(),
        ]);
    }

    /**
     * Создание объявления
     */
    public function create(Request $request): View
    {
        $this->checkCreationOpen();

        $boards = (new Board())->getChildren();

        if ($boards->isEmpty()) {
            abort(200, __('board::boards.categories_not_created'));
        }

        $bid = int($request->input('bid'));

        $files = File::query()
            ->pending(Item::$morphName, getUser('id'))
            ->ordered()
            ->get();

        return view('board::boards/create', compact('boards', 'bid', 'files'));
    }

    /**
     * Публикация объявления
     */
    public function store(ItemStoreRequest $request, Flood $flood, FileService $files): RedirectResponse
    {
        $this->checkCreationOpen();

        $item = Item::query()->create($request->itemFields() + [
            'user_id'    => getUser('id'),
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->addDays((int) setting('boards_period')),
        ]);

        $item->category->increment('count_items');

        $files->attach($item, $request->file('files', []));

        clearCache(['statBoards', 'recentBoards']);
        $flood->saveState();

        return redirect()->route('items.view', ['id' => $item->id])
            ->with('success', __('board::boards.item_success_added'));
    }

    /**
     * Редактирование объявления
     */
    public function edit(int $id): View|RedirectResponse
    {
        $item = $this->findItem($id);

        // Форма только для автора: админ правит чужое объявление в админке
        if ($item->user_id !== getUser('id') && isAdmin()) {
            return redirect()->route('admin.items.edit', ['id' => $item->id]);
        }

        $this->checkAuthor($item);

        $boards = $item->category->getChildren();

        return view('board::boards/edit', compact('item', 'boards'));
    }

    /**
     * Сохранение объявления
     */
    public function update(int $id, ItemRequest $request): RedirectResponse
    {
        $item = $this->findItem($id);
        $this->checkAuthor($item);

        $item->moveTo($request->category());
        $item->update($request->itemFields());

        clearCache(['statBoards', 'recentBoards']);

        return redirect()->route('items.view', ['id' => $item->id])
            ->with('success', __('board::boards.item_success_edited'));
    }

    /**
     * Снятие / Публикация объявления
     */
    public function close(int $id): RedirectResponse
    {
        $item = $this->findItem($id);

        if ($item->user_id !== getUser('id')) {
            return redirect()->route('items.view', ['id' => $item->id])
                ->withErrors(__('board::boards.item_not_author'));
        }

        if ($item->expires_at->gt(now())) {
            $item->unpublish();
            $status = __('board::boards.item_success_unpublished');
        } else {
            $item->publish();
            $status = __('board::boards.item_success_published');
        }

        return redirect()->route('items.edit', ['id' => $item->id])
            ->with('success', $status);
    }

    /**
     * Удаление объявления
     */
    public function delete(int $id): RedirectResponse
    {
        $item = $this->findItem($id);

        if ($item->user_id !== getUser('id')) {
            return redirect()->route('boards.index', ['id' => $item->board_id])
                ->withErrors(__('board::boards.item_not_author'));
        }

        $item->delete();

        clearCache(['statBoards', 'recentBoards']);

        return redirect()->route('boards.index', ['id' => $item->board_id])
            ->with('success', __('board::boards.item_success_deleted'));
    }

    /**
     * Мои объявления
     */
    public function active(Request $request): View
    {
        $user = getUser();

        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'desc');

        [$sorting, $orderBy] = Item::getSorting($sort, $order);

        $type = $request->input('type') === 'archive' ? 'archive' : 'active';
        $whereType = match ($type) {
            'active' => '>',
            default  => '<=',
        };

        $otherType = $type === 'active' ? '<=' : '>';
        $otherCount = Item::query()
            ->where('user_id', $user->id)
            ->where('expires_at', $otherType, now())
            ->count();

        $items = Item::query()
            ->where('user_id', $user->id)
            ->where('expires_at', $whereType, now())
            ->orderBy(...$orderBy)
            ->with('category', 'user', 'files')
            ->paginate(setting('boards_per_page'))
            ->appends(compact('type', 'sort', 'order'));

        return view('board::boards/active', compact('items', 'sort', 'order', 'sorting', 'type', 'otherCount'));
    }

    /**
     * Объявление по id
     */
    private function findItem(int $id): Item
    {
        $item = Item::query()->find($id);

        if (! $item) {
            abort(404, __('board::boards.item_not_exist'));
        }

        return $item;
    }

    /**
     * Править объявление может только автор
     */
    private function checkAuthor(Item $item): void
    {
        if ($item->user_id !== getUser('id')) {
            abort(403, __('board::boards.item_not_author'));
        }
    }

    /**
     * Подача объявлений может быть закрыта настройкой — кроме администрации
     */
    private function checkCreationOpen(): void
    {
        if (! isAdmin() && ! setting('boards_create')) {
            abort(200, __('board::boards.boards_closed'));
        }
    }
}
