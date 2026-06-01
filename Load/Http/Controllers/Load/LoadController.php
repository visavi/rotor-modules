<?php

declare(strict_types=1);

namespace Modules\Load\Http\Controllers\Load;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;

class LoadController extends Controller
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $categories = Load::query()
            ->where('parent_id', 0)
            ->with('children', 'new', 'children.new', 'lastDown.user')
            ->orderBy('sort')
            ->get();

        if ($categories->isEmpty()) {
            abort(200, __('load::loads.empty_loads'));
        }

        return view('load::downs/index', compact('categories'));
    }

    /**
     * Список файлов в категории
     */
    public function load(int $id, Request $request): View
    {
        $category = Load::query()->with('parent')->find($id);

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

        return view('load::downs/load', compact('category', 'downs', 'sorting'));
    }

    /**
     * RSS всех файлов
     */
    public function rss(): Response
    {
        $downs = Down::query()
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        if ($downs->isEmpty()) {
            abort(200, __('load::loads.downs_not_found'));
        }

        return response()
            ->view('load::downs/rss', compact('downs'))
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}
