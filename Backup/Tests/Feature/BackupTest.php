<?php

namespace Modules\Backup\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\ModuleTestCase;

class BackupTest extends ModuleTestCase
{
    protected string $moduleName = 'Backup';

    private User $boss;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->boss = User::factory()->boss()->create(['login' => 'boss_test']);
        $this->admin = User::factory()->admin()->create(['login' => 'admin_test']);
    }

    public function testBackupIndexRequiresBoss(): void
    {
        $response = $this->get('/admin/backups');
        $response->assertRedirect();
    }

    public function testBackupIndexForbiddenForAdmin(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/backups');
        $response->assertForbidden();
    }

    public function testBackupIndexAccessibleByBoss(): void
    {
        $response = $this->actingAs($this->boss)->get('/admin/backups');
        $response->assertOk();
    }

    public function testDumpKeepsBackslashes(): void
    {
        $value = 'C:\\path\\ и \'кавычка\'';
        User::factory()->create(['info' => $value]);

        $before = $this->backups();

        try {
            $this->actingAs($this->boss)
                ->post('/admin/backups/create', ['sheets' => ['users'], 'method' => 'none', 'level' => 0])
                ->assertRedirect('admin/backups')
                ->assertSessionHas('success');

            $created = array_values(array_diff($this->backups(), $before));
            $this->assertCount(1, $created);

            $dump = (string) file_get_contents($created[0]);

            // Строка экранирована соединением: обратная косая черта не съедает кавычку
            $this->assertStringContainsString(DB::getPdo()->quote($value), $dump);

            // Колонки в кавычках, строки по первичному ключу
            $this->assertStringContainsString('INSERT INTO `users` (`id`,`login`,', $dump);
            $this->assertLessThan(strpos($dump, "'admin_test'"), strpos($dump, "'boss_test'"));
        } finally {
            array_map(unlink(...), array_diff($this->backups(), $before));
        }
    }

    public function testIndexListsNewestFirst(): void
    {
        // По имени апрель шёл бы раньше января: сортировка — по времени файла
        $january = storage_path('backups/backup_10-Jan-2026_10-00-00_test.sql');
        $april = storage_path('backups/backup_10-Apr-2026_10-00-00_test.sql');

        try {
            file_put_contents($april, '-- april');
            file_put_contents($january, '-- january');
            touch($april, strtotime('2026-04-10'));
            touch($january, strtotime('2026-01-10'));

            $this->actingAs($this->boss)
                ->get('/admin/backups')
                ->assertOk()
                ->assertSeeInOrder([basename($april), basename($january)])
                ->assertSee('data-confirm="' . __('backup::backup.confirm_delete') . '"', false);
        } finally {
            array_map(unlink(...), array_filter([$january, $april], is_file(...)));
        }
    }

    public function testGzipDumpIsReadable(): void
    {
        if (! function_exists('gzopen')) {
            $this->markTestSkipped('zlib не установлен');
        }

        $before = $this->backups();

        try {
            $this->actingAs($this->boss)
                ->post('/admin/backups/create', ['sheets' => ['users'], 'method' => 'gzip', 'level' => 5])
                ->assertSessionHas('success');

            $created = array_values(array_diff($this->backups(), $before));
            $this->assertCount(1, $created);
            $this->assertStringEndsWith('.sql.gz', $created[0]);
            $this->assertStringContainsString("'boss_test'", (string) gzdecode((string) file_get_contents($created[0])));
        } finally {
            array_map(unlink(...), array_diff($this->backups(), $before));
        }
    }

    public function testDeleteRemovesBackup(): void
    {
        $file = storage_path('backups/backup_01-Jan-2026_00-00-00.sql');
        file_put_contents($file, '-- dump');

        try {
            $this->actingAs($this->boss)
                ->delete('/admin/backups/delete', ['file' => basename($file)])
                ->assertRedirect('admin/backups')
                ->assertSessionHas('success');

            $this->assertFileDoesNotExist($file);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testCreateErrorsAreShownUnderTheirField(): void
    {
        $this->actingAs($this->boss)
            ->post('/admin/backups/create', ['method' => 'zip', 'level' => 12])
            ->assertSessionHasErrors(['sheets', 'method', 'level']);
    }

    public function testDeleteRejectsForeignPath(): void
    {
        $this->actingAs($this->boss)
            ->delete('/admin/backups/delete', ['file' => '../../.env'])
            ->assertSessionHasErrors();

        $this->assertFileExists(base_path('.env'));
    }

    /**
     * Файлы бэкапов в каталоге хранения
     *
     * @return list<string>
     */
    private function backups(): array
    {
        return glob(storage_path('backups/*.{gz,bz2,sql}'), GLOB_BRACE) ?: [];
    }
}
