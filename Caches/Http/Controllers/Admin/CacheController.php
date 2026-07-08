<?php

declare(strict_types=1);

namespace Modules\Caches\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
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

        $files = match ($type) {
            'views' => glob(storage_path('framework/views/*.php'), GLOB_BRACE),
            default => glob(storage_path('framework/cache/data/*/*/*')),
        };

        $files = paginate($files, 20, compact('type'));

        return view('caches::admin/index', compact('files', 'type'));
    }

    /**
     * Очистка кеша
     */
    public function clear(Request $request): RedirectResponse
    {
        $type = $request->input('type');

        if ($type === 'views') {
            Artisan::call('view:clear');
        } else {
            refreshCaches();
        }

        setFlash('success', __('caches::caches.success_cleared'));

        return redirect()->route('admin.caches.index', ['type' => $type]);
    }
}
