<?php

namespace Modules\Board\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Board\Models\Board;
use Modules\Board\Models\Item;
use Tests\ModuleTestCase;

class BoardSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Board';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Item::$morphName => Item::class]);

        $this->user = User::factory()->create();
    }

    public function testIndex(): void
    {
        $this->get(route('boards.index'))->assertOk();
    }

    public function testCategory(): void
    {
        $board = Board::query()->create(['name' => 'Test board']);

        $this->get(route('boards.index', ['id' => $board->id]))->assertOk();
    }

    public function testItem(): void
    {
        $board = Board::query()->create(['name' => 'Test board']);

        $item = Item::query()->create([
            'board_id'   => $board->id,
            'title'      => 'Test item',
            'text'       => 'Test item text',
            'user_id'    => $this->user->id,
            'created_at' => SITETIME,
            'updated_at' => SITETIME,
            'expires_at' => SITETIME + 86400,
        ]);

        $this->get(route('items.view', ['id' => $item->id]))->assertOk();
    }
}
