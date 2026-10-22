<?php

namespace Modules\Game\Tests\Feature;

use App\Models\User;
use Tests\ModuleTestCase;

class GameBetTest extends ModuleTestCase
{
    protected string $moduleName = 'Game';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('bonusmoney', 0);

        $this->user = User::factory()->create(['money' => 1000]);
    }

    public function testAjaxBetErrorComesAsValidationJson(): void
    {
        // Ajax ядра сам сводит 422 к {success, message}
        $this->actingAs($this->user)
            ->postJson('/games/roulette/spin', ['bet' => 0, 'type' => 'unknown'])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'bet'  => __('game::games.bj_bet_required'),
                'type' => __('game::games.roulette_bet_invalid'),
            ]);

        $this->assertSame(1000, $this->user->fresh()->money);
    }

    public function testFormBetErrorsAreShownUnderTheirField(): void
    {
        $this->actingAs($this->user)
            ->from('/games/roulette')
            ->post('/games/roulette/spin', ['bet' => 5000, 'type' => 'number', 'number' => 40])
            ->assertRedirect('games/roulette')
            ->assertSessionHasErrors([
                'bet'    => __('game::games.not_enough_money'),
                'number' => __('game::games.roulette_number_invalid'),
            ]);

        $this->actingAs($this->user)
            ->post('/games/miner/bet', ['bet' => 100, 'mines' => 99])
            ->assertSessionHasErrors('mines');

        $this->assertSame(1000, $this->user->fresh()->money);
    }
}
