<?php

declare(strict_types=1);

namespace Modules\Load\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\File;
use App\Support\CategoryTree;
use App\Support\Restatement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Load\Http\Requests\DownRequest;
use Modules\Load\Http\Requests\LoadCategoryRequest;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;

class LoadController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        // Дерево строит компонент: он же собирает поле порядка и обязан видеть
        // тот же список, поэтому раскладывать его здесь незачем
        $categories = Load::query()
            ->orderBy('sort')
            ->with('new', 'lastDown.user')
            ->get();

        // Счётчик раздела показывает всю ветку: вложенные загрузки принадлежат
        // ему не меньше собственных
        CategoryTree::totals($categories, ['count_downs' => 'total_downs']);

        $new = Down::query()
            ->active(false)
            ->count();

        return view('load::admin/downs/index', compact('categories', 'new'));
    }

    /**
     * Сохраняет порядок и вложенность разделов
     */
    public function sort(Request $request): RedirectResponse
    {
        CategoryTree::reorder(Load::class, $request->string('order')->toString());

        return redirect()
            ->route('admin.loads.index')
            ->with('success', __('load::loads.loads_success_sorted'));
    }

    /**
     * Создание раздела
     */
    public function create(LoadCategoryRequest $request): RedirectResponse
    {
        $load = Load::query()->create([
            'name' => $request->string('name')->value(),
            'sort' => Load::query()->max('sort') + 1,
        ]);

        return redirect()->route('admin.loads.edit', ['id' => $load->id])
            ->with('success', __('load::loads.load_success_created'));
    }

    /**
     * Редактирование раздела
     */
    public function edit(int $id): View
    {
        $load = $this->findLoad($id);
        $loads = $load->getChildren();

        return view('load::admin/downs/edit', compact('loads', 'load'));
    }

    /**
     * Сохранение раздела
     */
    public function update(int $id, LoadCategoryRequest $request): RedirectResponse
    {
        $this->findLoad($id)->update([
            'parent_id' => $request->integer('parent'),
            'name'      => $request->string('name')->value(),
            'closed'    => $request->boolean('closed'),
        ]);

        return redirect()->route('admin.loads.index')
            ->with('success', __('load::loads.load_success_edited'));
    }

    /**
     * Удаление раздела
     */
    public function delete(int $id): RedirectResponse
    {
        $load = $this->findLoad($id);
        $redirect = redirect()->route('admin.loads.index');

        if ($load->children->isNotEmpty()) {
            return $redirect->withErrors(__('load::loads.load_has_subcategories'));
        }

        if (Down::query()->where('category_id', $load->id)->exists()) {
            return $redirect->withErrors(__('load::loads.load_has_downs'));
        }

        $load->delete();

        return $redirect->with('success', __('load::loads.load_success_deleted'));
    }

    /**
     * Пересчет данных
     */
    public function restatement(): RedirectResponse
    {
        Restatement::run('loads');

        return redirect()
            ->route('admin.loads.index')
            ->with('success', __('main.success_recounted'));
    }

    /**
     * Просмотр загрузок раздела
     */
    public function load(int $id, Request $request): View
    {
        // Дерево целиком: счётчик подраздела складывает всю его ветку
        $categories = Load::query()->orderBy('sort')->get();

        CategoryTree::totals($categories, ['count_downs' => 'total_downs']);
        CategoryTree::nest($categories);

        $category = $categories->firstWhere('id', $id);

        if (! $category) {
            abort(404, __('load::loads.load_not_exist'));
        }

        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'desc');

        [$sorting, $orderBy] = Down::getSorting($sort, $order);

        $downs = Down::query()
            ->active()
            ->where('category_id', $category->id)
            ->orderBy(...$orderBy)
            ->with('user')
            ->paginate(setting('downlist'))
            ->appends(compact('sort', 'order'));

        return view('load::admin/downs/load', compact('category', 'downs', 'sorting'));
    }

    /**
     * Редактирование загрузки
     */
    public function editDown(int $id, Request $request): View
    {
        $cid = int($request->input('category'));
        $down = $this->findDown($id);

        $files = File::query()
            ->where('relate_type', Down::$morphName)
            ->where('relate_id', $down->id)
            ->ordered()
            ->get();

        $categories = $down->category->getChildren();

        return view('load::admin/downs/edit_down', compact('categories', 'down', 'cid', 'files'));
    }

    /**
     * Сохранение загрузки
     */
    public function updateDown(int $id, DownRequest $request): RedirectResponse
    {
        $down = $this->findDown($id);
        $oldCategory = $down->category;

        $down->update([
            'category_id' => $request->category()->id,
            'title'       => $request->string('title')->value(),
            'text'        => $request->string('text')->value(),
            'links'       => $request->links(),
        ]);

        // Счётчик ведёт только опубликованные загрузки
        if ($down->active && $down->category_id !== $oldCategory->id) {
            $request->category()->increment('count_downs');
            $oldCategory->decrement('count_downs');
        }

        if (! $down->active && $down->user->exists) {
            $text = textNotice('down_change', ['url' => route('downs.view', ['id' => $down->id], false), 'title' => $down->title]);
            $down->user->sendMessage(null, $text);
        }

        clearCache(['statLoads', 'recentDowns']);

        return redirect()->route('admin.downs.edit', ['id' => $down->id])
            ->with('success', __('load::loads.down_edited_success'));
    }

    /**
     * Удаление загрузки
     */
    public function deleteDown(int $id): RedirectResponse
    {
        $down = Down::query()->findOrFail($id);

        if ($down->active) {
            $down->category->decrement('count_downs');
        }

        $down->delete();

        clearCache(['statLoads', 'recentDowns']);

        return redirect()->route('admin.loads.load', ['id' => $down->category_id])
            ->with('success', __('load::loads.down_success_deleted'));
    }

    /**
     * Новые публикации
     */
    public function new(): View
    {
        $downs = Down::query()
            ->active(false)
            ->orderByDesc('created_at')
            ->with('user', 'category', 'files')
            ->paginate(setting('downlist'));

        return view('load::admin/downs/new', compact('downs'));
    }

    /**
     * Публикация загрузки
     */
    public function publish(int $id): RedirectResponse
    {
        $down = Down::query()->findOrFail($id);

        $active = $down->active ^ 1;

        // created_at сбрасываем намеренно: при публикации загрузка всплывает как новая
        // (списки и сортировка по created_at); updated_at проставит авто-таймстамп
        $down->update([
            'active'     => $active,
            'created_at' => now(),
        ]);

        $user = $down->user;
        $point = (int) setting('down_point');
        $money = (int) setting('down_money');

        if ($active) {
            $status = __('load::loads.down_success_published');
            $down->category->increment('count_downs');
            $text = textNotice('down_publish', ['url' => route('downs.view', ['id' => $down->id], false), 'title' => $down->title]);
        } else {
            $status = __('load::loads.down_success_unpublished');
            $down->category->decrement('count_downs');
            $text = textNotice('down_unpublish', ['url' => route('downs.view', ['id' => $down->id], false), 'title' => $down->title]);
        }

        // Автора могли удалить: withDefault отдаёт пустую модель, и increment на ней
        // обновил бы всех пользователей. Снятие забирает награду, но не больше остатка
        if ($user->exists) {
            if ($active) {
                $user->increment('point', $point);
                $user->increment('money', $money);
            } else {
                $user->decrement('point', min($point, $user->point));
                $user->decrement('money', min($money, $user->money));
            }

            $user->sendMessage(null, $text);
        }

        clearCache(['statLoads', 'recentDowns']);

        return redirect()->route('admin.downs.edit', ['id' => $down->id])
            ->with('success', $status);
    }

    /**
     * Раздел по id
     */
    private function findLoad(int $id): Load
    {
        $load = Load::query()->with('children')->findOrFail($id);

        return $load;
    }

    /**
     * Загрузка по id
     */
    private function findDown(int $id): Down
    {
        $down = Down::query()->findOrFail($id);

        return $down;
    }
}
