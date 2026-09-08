<?php

namespace Modules\PageEditor\Tests\Feature;

use Illuminate\Support\Facades\File;
use Modules\PageEditor\Support\FileWriter;
use RuntimeException;
use Tests\ModuleTestCase;

class FileWriterTest extends ModuleTestCase
{
    protected string $moduleName = 'PageEditor';

    private string $file;

    /**
     * Боевой storage/app/page-editor/backups — единственный способ откатить
     * сломанный blade, тесты в него не лезут
     */
    protected function moduleConfig(): array
    {
        return ['backup_path' => storage_path('framework/testing/page-editor/backups')];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = resource_path('views/page_editor_fixture.blade.php');
        $this->deleteTestingDirectory(FileWriter::backupDir());
    }

    protected function tearDown(): void
    {
        File::delete($this->file);
        $this->deleteTestingDirectory(FileWriter::backupDir());

        parent::tearDown();
    }

    public function testPutWritesContents(): void
    {
        FileWriter::put($this->file, 'first');

        $this->assertSame('first', file_get_contents($this->file));
    }

    public function testPutBackupsExistingFileOnly(): void
    {
        FileWriter::put($this->file, 'first');

        $this->assertDirectoryDoesNotExist(FileWriter::backupDir());

        FileWriter::put($this->file, 'second');

        $backups = File::files(FileWriter::backupDir());

        $this->assertCount(1, $backups);
        $this->assertSame('first', file_get_contents($backups[0]->getPathname()));
        $this->assertSame('second', file_get_contents($this->file));
    }

    public function testDeleteBackupsAndRemoves(): void
    {
        FileWriter::put($this->file, 'first');
        FileWriter::delete($this->file);

        $this->assertFileDoesNotExist($this->file);
        $backups = File::files(FileWriter::backupDir());
        $this->assertCount(1, $backups);
        $this->assertSame('first', file_get_contents($backups[0]->getPathname()));
    }

    public function testBackupFailurePreventsPut(): void
    {
        // Создаём файл
        FileWriter::put($this->file, 'original');

        // Делаем директорию для бэкапов недоступной
        $backupDir = FileWriter::backupDir();
        File::makeDirectory($backupDir, 0755, true);
        chmod($backupDir, 0444); // только чтение

        $this->expectException(RuntimeException::class);

        try {
            FileWriter::put($this->file, 'new content');
        } finally {
            chmod($backupDir, 0755);
            File::deleteDirectory($backupDir);
        }

        // Убеждаемся, что файл НЕ был перезаписан
        $this->assertSame('original', file_get_contents($this->file));
    }

    public function testBackupFailurePreventsDeletion(): void
    {
        // Создаём файл
        FileWriter::put($this->file, 'content');

        // Делаем директорию для бэкапов недоступной
        $backupDir = FileWriter::backupDir();
        File::makeDirectory($backupDir, 0755, true);
        chmod($backupDir, 0444); // только чтение

        $this->expectException(RuntimeException::class);

        try {
            FileWriter::delete($this->file);
        } finally {
            chmod($backupDir, 0755);
            File::deleteDirectory($backupDir);
        }

        // Убеждаемся, что файл НЕ был удалён
        $this->assertFileExists($this->file);
        $this->assertSame('content', file_get_contents($this->file));
    }

    public function testRapidWritesCreateUniqueBackups(): void
    {
        // Первая запись — бэкапа нет
        FileWriter::put($this->file, 'first');
        $this->assertDirectoryDoesNotExist(FileWriter::backupDir());

        // Вторая запись — бэкап первого содержимого
        FileWriter::put($this->file, 'second');
        $backups = File::files(FileWriter::backupDir());
        $this->assertCount(1, $backups);
        $this->assertSame('first', file_get_contents($backups[0]->getPathname()));

        // Третья запись без задержки — второй бэкап с ДРУГИМ содержимым
        FileWriter::put($this->file, 'third');
        $backups = File::files(FileWriter::backupDir());
        $this->assertCount(2, $backups);

        // Проверяем, что содержимое разных бэкапов отличается
        $backupContents = [
            file_get_contents($backups[0]->getPathname()),
            file_get_contents($backups[1]->getPathname()),
        ];

        $this->assertEqualsCanonicalizing(['first', 'second'], $backupContents);
        $this->assertSame('third', file_get_contents($this->file));
    }
}
