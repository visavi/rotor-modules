<?php

declare(strict_types=1);

namespace Modules\Caches\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Services\ThumbnailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class CacheController extends AdminController
{
    /**
     * Главная страница
     */
    public function index(Request $request): View
    {
        $type = $request->input('type', 'files');

        // Превью могут исчисляться тысячами: показываем сводку по каталогам, а не список
        if ($type === 'thumbnails') {
            $thumbnails = ThumbnailService::stats();

            return view('caches::admin/index', compact('thumbnails', 'type'));
        }

        $files = match ($type) {
            'views' => glob(storage_path('framework/views/*.php'), GLOB_BRACE),
            default => glob(storage_path('framework/cache/data/*/*/*')),
        } ?: [];

        $files = paginate($files, 20, compact('type'));

        return view('caches::admin/index', compact('files', 'type'));
    }

    /**
     * Очистка кеша
     */
    public function clear(Request $request): RedirectResponse
    {
        $type = $request->input('type');

        $cleared = true;

        if ($type === 'views') {
            Artisan::call('view:clear');
        } elseif ($type === 'thumbnails') {
            // На хостинге часть превью может принадлежать другому пользователю — их не удалить
            $cleared = ThumbnailService::clear();
        } else {
            refreshCaches();
        }

        $redirect = redirect()->route('admin.caches.index', ['type' => $type]);

        return $cleared
            ? $redirect->with('success', __('caches::caches.success_cleared'))
            : $redirect->with('danger', __('caches::caches.clear_failed'));
    }
}
