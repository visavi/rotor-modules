<?php

declare(strict_types=1);

namespace Modules\PageEditor\Support;

use Illuminate\Support\Str;
use RuntimeException;

class FileWriter
{
    /**
     * Возвращает директорию резервных копий
     */
    public static function backupDir(): string
    {
        return (string) config('page_editor.backup_path', storage_path('app/page-editor/backups'));
    }

    /**
     * Записывает файл, сохранив резервную копию
     */
    public static function put(string $path, string $contents): void
    {
        // Backup existing file (if it exists). Failure prevents write.
        self::backup($path);

        $directory = dirname($path);

        if (! is_dir($directory)) {
            if (@mkdir($directory, 0755, true) === false) {
                throw new RuntimeException("Failed to create directory for file: {$path}");
            }
        }

        if (@file_put_contents($path, $contents) === false) {
            throw new RuntimeException("Failed to write file: {$path}");
        }

        self::invalidate($path);
    }

    /**
     * Удаляет файл, сохранив резервную копию
     */
    public static function delete(string $path): void
    {
        // Backup existing file (if it exists). Failure prevents delete.
        self::backup($path);

        if (@unlink($path) === false) {
            throw new RuntimeException("Failed to delete file: {$path}");
        }

        self::invalidate($path);
    }

    /**
     * Сохраняет резервную копию существующего файла
     *
     * Если файл не существует, возвращает true (нечего копировать).
     * Если файл существует, создаёт резервную копию.
     * Если копирование не удалось, бросает RuntimeException.
     *
     * @throws RuntimeException если резервная копия не создалась
     */
    private static function backup(string $path): void
    {
        if (! is_file($path)) {
            return; // Нечего копировать
        }

        $directory = self::backupDir();

        if (! is_dir($directory)) {
            if (@mkdir($directory, 0755, true) === false) {
                throw new RuntimeException("Failed to create backup directory: {$directory}");
            }
        }

        $name = trim(Str::after($path, base_path()), '/');
        $name = str_replace('/', '_', $name);

        // Используем DateTimeImmutable для уникальности имени с микросекундами
        $now = new \DateTimeImmutable();
        $timestamp = $now->format('YmdHis_u');

        $backupPath = $directory . '/' . $name . '.' . $timestamp . '.bak';

        // На случай коллизии (крайне редко), добавляем счётчик
        $counter = 0;
        while (is_file($backupPath)) {
            $counter++;
            $backupPath = $directory . '/' . $name . '.' . $timestamp . '_' . $counter . '.bak';
        }

        if (@copy($path, $backupPath) === false) {
            throw new RuntimeException("Failed to create backup for file: {$path}");
        }
    }

    /**
     * Сбрасывает opcache по файлу
     *
     * На проде opcache.validate_timestamps=0: без этого FPM продолжит отдавать
     * старую версию файла, тогда как CLI видит свежую
     */
    private static function invalidate(string $path): void
    {
        if (Str::endsWith($path, '.php') && function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }
}
