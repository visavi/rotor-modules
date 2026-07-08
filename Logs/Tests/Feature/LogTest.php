<?php

namespace Modules\Logs\Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Modules\Logs\Console\DeleteLogs;
use Modules\Logs\Models\Log;
use Tests\ModuleTestCase;

class LogTest extends ModuleTestCase
{
    protected string $moduleName = 'Logs';

    private User $boss;

    protected function setUp(): void
    {
        parent::setUp();

        $this->boss = User::factory()->boss()->create(['login' => 'boss_test']);

        // Команды модулей регистрирует ModuleServiceProvider по активным модулям,
        // в тестах таблица modules пустая — регистрируем вручную
        $this->app[Kernel::class]->registerCommand(new DeleteLogs());
    }

    public function testIndexRequiresBoss(): void
    {
        $response = $this->get('/admin/logs');
        $response->assertRedirect();
    }

    public function testIndexAccessibleByBoss(): void
    {
        Log::query()->create([
            'user_id' => $this->boss->id,
            'request' => '/admin/test',
            'referer' => '/admin',
            'ip'      => '127.0.0.1',
            'brow'    => 'Chrome 120',
        ]);

        $response = $this->actingAs($this->boss)->get('/admin/logs');
        $response->assertOk();
        $response->assertSee('/admin/test');
    }

    public function testClearByBoss(): void
    {
        Log::query()->create([
            'user_id' => $this->boss->id,
            'request' => '/admin/test',
            'referer' => '/admin',
            'ip'      => '127.0.0.1',
            'brow'    => 'Chrome 120',
        ]);

        $response = $this->actingAs($this->boss)->post('/admin/logs/clear');
        $response->assertRedirect(route('admin.logs.index'));

        $this->assertSame(0, Log::query()->count());
    }

    public function testDeleteLogsCommand(): void
    {
        Log::query()->create([
            'user_id'    => $this->boss->id,
            'request'    => '/admin/old',
            'referer'    => '/admin',
            'ip'         => '127.0.0.1',
            'brow'       => 'Chrome 120',
            'created_at' => now()->subMonths(2),
        ]);

        Log::query()->create([
            'user_id' => $this->boss->id,
            'request' => '/admin/fresh',
            'referer' => '/admin',
            'ip'      => '127.0.0.1',
            'brow'    => 'Chrome 120',
        ]);

        $this->artisan('delete:logs')->assertSuccessful();

        $this->assertSame(1, Log::query()->count());
        $this->assertSame('/admin/fresh', Log::query()->value('request'));
    }
}
