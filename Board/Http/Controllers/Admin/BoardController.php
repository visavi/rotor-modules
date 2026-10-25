<?php

declare(strict_types=1);

namespace Modules\Board\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Support\CategoryTree;
use App\Support\Restatement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Board\Http\Requests\BoardCategoryRequest;
use Modules\Board\Http\Requests\ItemRequest;
use Modules\Board\Models\Board;
use Modules\Board\Models\Item;

class BoardController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(Request $request, ?int $id = null): View
    {
        $board = null;

        if ($id) {
            $board = Board::query()->findOrFail($id);
        }

        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'desc');

        [$sorting, $orderBy] = Item::getSorting($sort, $order);

        $items = Item::query()
            ->when($board, static function (Builder $query, Board $board) {
                return $query->where('board_id', $board->id);
            })
            ->where('expires_at', '>', now())
            ->orderBy(...$orderBy)
            ->with('category', 'user', 'files')
            ->paginate(setting('boards_per_page'))
            ->appends(compact('sort', 'order'));

        $boards = Board::query()
            ->where('parent_id', $board->id ?? 0)
            ->with('children')
            ->get();

        return view('board::admin/boards/index', compact('items', 'board', 'boards', 'sorting'));
    }

    /**
     * Категории объявлений
     */
    public function categories(): View
    {
        // Дерево строит компонент: он же собирает поле порядка и обязан видеть
        // тот же список, поэтому раскладывать его здесь незачем
        $boards = Board::query()->orderBy('sort')->get();

        // Счётчик категории показывает всю ветку: вложенные объявления
        // принадлежат ей не меньше собственных
        CategoryTree::totals($boards, ['count_items' => 'total_items']);

        return view('board::admin/boards/categories', compact('boards'));
    }

    /**
     * Сохраняет порядок и вложенность категорий
     */
    public function sort(Request $request): RedirectResponse
    {
        $order = $request->input('order');

        CategoryTree::reorder(Board::class, is_string($order) ? $order : '');

        return redirect()
            ->route('admin.boards.categories')
            ->with('success', __('board::boards.categories_success_sorted'));
    }

    /**
     * Создание раздела
     */
    public function create(BoardCategoryRequest $request): RedirectResponse
    {
        $board = Board::query()->create([
            'name' => $request->string('name')->value(),
            'sort' => Board::query()->max('sort') + 1,
        ]);

        return redirect()->route('admin.boards.edit', ['id' => $board->id])
            ->with('success', __('board::boards.category_success_created'));
    }

    /**
     * Редактирование раздела
     */
    public function edit(int $id): View
    {
        $board = $this->findCategory($id);
        $boards = $board->getChildren();

        return view('board::admin/boards/edit', compact('boards', 'board'));
    }

    /**
     * Сохранение раздела
     */
    public function update(int $id, BoardCategoryRequest $request): RedirectResponse
    {
        $board = $this->findCategory($id);

        $board->update([
            'parent_id' => $request->integer('parent'),
            'name'      => $request->string('name')->value(),
            'closed'    => $request->boolean('closed'),
        ]);

        return redirect()->route('admin.boards.categories')
            ->with('success', __('board::boards.category_success_edited'));
    }

    /**
     * Удаление раздела
     */
    public function delete(int $id): RedirectResponse
    {
        $board = $this->findCategory($id);
        $redirect = redirect()->route('admin.boards.categories');

        if ($board->children->isNotEmpty()) {
            return $redirect->withErrors(__('board::boards.category_has_subsections'));
        }

        if (Item::query()->where('board_id', $board->id)->exists()) {
            return $redirect->withErrors(__('board::boards.category_has_items'));
        }

        $board->delete();

        return $redirect->with('success', __('board::boards.category_success_deleted'));
    }

    /**
     * Редактирование объявления
     */
    public function editItem(int $id): View
    {
        $item = $this->findItem($id);
        $boards = $item->category->getChildren();

        return view('board::admin/boards/edit_item', compact('item', 'boards'));
    }

    /**
     * Сохранение объявления
     */
    public function updateItem(int $id, ItemRequest $request): RedirectResponse
    {
        $item = $this->findItem($id);
        $item->moveTo($request->category());
        $item->update($request->itemFields());

        return redirect()->route('admin.items.edit', ['id' => $item->id])
            ->with('success', __('board::boards.item_success_edited'));
    }

    /**
     * Удаление объявления
     */
    public function deleteItem(int $id): RedirectResponse
    {
        $item = $this->findItem($id);

        $item->delete();

        return redirect()->route('admin.boards.index', ['id' => $item->board_id])
            ->with('success', __('board::boards.item_success_deleted'));
    }

    /**
     * Пересчет объявлений
     */
    public function restatement(): RedirectResponse
    {
        Restatement::run('boards');

        return redirect()
            ->route('admin.boards.index')
            ->with('success', __('main.success_recounted'));
    }

    /**
     * Категория по id
     */
    private function findCategory(int $id): Board
    {
        $board = Board::query()->with('children')->findOrFail($id);

        return $board;
    }

    /**
     * Объявление по id
     */
    private function findItem(int $id): Item
    {
        $item = Item::query()->findOrFail($id);

        return $item;
    }
}
