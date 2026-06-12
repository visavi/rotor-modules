<?php

namespace Modules\Load\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;
use Tests\ModuleTestCase;

class LoadSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Load';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Down::$morphName => Down::class]);

        $this->user = User::factory()->create();
    }

    public function testIndex(): void
    {
        $this->get(route('loads.index'))->assertOk();
    }

    public function testCategory(): void
    {
        $load = Load::query()->create(['name' => 'Test category']);

        $this->get(route('loads.load', ['id' => $load->id]))->assertOk();
    }

    public function testDown(): void
    {
        $load = Load::query()->create(['name' => 'Test category']);

        $down = Down::query()->create([
            'category_id' => $load->id,
            'title'       => 'Test down',
            'text'        => 'Test down text',
            'user_id'     => $this->user->id,
            'active'      => true,
            'created_at'  => SITETIME,
        ]);

        $this->get(route('downs.view', ['id' => $down->id]))->assertOk();
    }
}
