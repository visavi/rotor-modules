<?php

declare(strict_types=1);

namespace Modules\Load\Observers;

use App\Models\File;
use Modules\Load\Services\ZipMap;

class FileObserver
{
    /**
     * Убирает карту удалённого архива, чтобы не лежала в кеше до истечения срока
     */
    public function deleted(File $file): void
    {
        if ($file->extension === 'zip') {
            ZipMap::forget($file);
        }
    }
}
