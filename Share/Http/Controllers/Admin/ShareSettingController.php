<?php

declare(strict_types=1);

namespace Modules\Share\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class ShareSettingController extends ModuleSettingController
{
    protected string $view = 'share::admin/settings/_share';

    protected string $route = 'share.settings';

    /**
     * Переключатель на каждую соцсеть из config.php и на копирование ссылки
     */
    protected function rules(): array
    {
        /** @var array<string, mixed> $networks */
        $networks = config('share.networks');
        $keys = [...array_keys($networks), 'copy'];

        return array_fill_keys(
            array_map(static fn (string $key): string => 'sets.share_' . $key, $keys),
            ['required', 'boolean'],
        );
    }
}
