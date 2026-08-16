<?php

declare(strict_types=1);

namespace Modules\Load\Services;

use App\Models\File;
use Illuminate\Support\Facades\Cache;
use ZipArchive;

/**
 * Карта содержимого архива: имена, размеры и индексы записей
 *
 * Кэшируется на файл, а не на запрос: содержимое загруженного архива
 * не меняется, а страницы просмотра перебирают боты по всем индексам
 */
class ZipMap
{
    /**
     * Время жизни карты
     */
    private const int LIFETIME = 86400;

    /**
     * Плоский список записей архива
     *
     * @return list<array<string, mixed>>
     */
    public static function entries(File $file, ZipArchive $archive): array
    {
        return Cache::remember(self::key($file), self::LIFETIME, static function () use ($archive): array {
            $entries = [];

            for ($i = 0; $i < $archive->count(); $i++) {
                $stat = $archive->statIndex($i);
                $isDir = str_ends_with($stat['name'], '/');

                $entries[] = [
                    'index' => $stat['index'],
                    'name'  => $stat['name'],
                    'size'  => $stat['size'],
                    'isDir' => $isDir,
                    'ext'   => $isDir ? '' : getExtension($stat['name']),
                ];
            }

            return $entries;
        });
    }

    /**
     * Есть ли в архиве запись с таким индексом
     *
     * Отвечает по кэшу, поэтому перебор несуществующих индексов
     * не доходит до открытия архива
     */
    public static function has(File $file, int $index): ?bool
    {
        $entries = Cache::get(self::key($file));

        if (! is_array($entries)) {
            return null;
        }

        foreach ($entries as $entry) {
            if ($entry['index'] === $index && ! $entry['isDir']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Сбрасывает карту — файл удалили или заменили
     */
    public static function forget(File $file): void
    {
        Cache::forget(self::key($file));
    }

    private static function key(File $file): string
    {
        return 'zipMap' . $file->id;
    }
}
