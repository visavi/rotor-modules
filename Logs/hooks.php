<?php

use App\Classes\Hook;
use App\Classes\Registry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Logs\Models\Log;

// Запись действий администраторов (вызывается из middleware admin.logger)
Registry::onAdminLog(static function (Request $request): void {
    Log::query()->create([
        'user_id' => auth()->id(),
        'request' => Str::substr($request->getRequestUri(), 0, 191),
        'referer' => Str::substr((string) $request->header('referer'), 0, 191),
        'ip'      => getIp(),
        'brow'    => getBrowser(),
    ]);
});

// Плитка логов посещений в админ-панели (блок босса)
Hook::add('adminBlockBoss', static function () {
    return '<div class="col">
        <a href="' . route('admin.logs.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#6c757d"><i class="fas fa-list-alt"></i></div>
            <div class="app-tile-label">' . __('logs::logs.title') . '</div>
        </a>
    </div>';
});
