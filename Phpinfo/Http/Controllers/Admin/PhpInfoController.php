<?php

declare(strict_types=1);

namespace Modules\Phpinfo\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PhpInfoController extends Controller
{
    /**
     * Просмотр информации о PHP
     */
    public function index(): View
    {
        if (! isAdmin(User::ADMIN)) {
            abort(403, __('errors.forbidden'));
        }

        $iniInfo = null;

        if (function_exists('ini_get_all')) {
            $iniInfo = ini_get_all();
        }

        // Без расширения GD функции нет: страница падала бы, а не показывала остальное
        $gdInfo = function_exists('gd_info') ? parseVersion(gd_info()['GD Version']) : null;

        $dbVersion = DB::select('SELECT VERSION() as version')[0]->version ?? 'N/A';

        return view('phpinfo::admin/phpinfo', compact('iniInfo', 'gdInfo', 'dbVersion'));
    }
}
