<?php

namespace Modules\Game\Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ModuleTestCase;

class GameSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Game';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('bonusmoney', 0);

        $this->user = User::factory()->create(['money' => 5000]);
    }

    public static function pageProvider(): array
    {
        return [
            'кости'      => ['/games/dices'],
            'напёрстки'  => ['/games/thimbles'],
            'автомат'    => ['/games/bandit'],
            'правила'    => ['/games/bandit/faq'],
            'блэкджек'   => ['/games/blackjack'],
            'правила бж' => ['/games/blackjack/rules'],
            'угадайка'   => ['/games/guess'],
            'сейф'       => ['/games/safe'],
        ];
    }

    public function testIndexIsOpenForGuests(): void
    {
        $this->get('/games')->assertOk();
    }

    #[DataProvider('pageProvider')]
    public function testGamePageIsOpen(string $url): void
    {
        $this->actingAs($this->user)->get($url)->assertOk();
    }

    #[DataProvider('pageProvider')]
    public function testGamePageNeedsAuth(string $url): void
    {
        $this->get($url)->assertForbidden();
    }

    public function testDiceRoundChangesMoney(): void
    {
        // Ничья оставляет деньги на месте, поэтому играем до первого исхода
        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($this->user)->get('/games/dices/go')->assertOk();

            if ($this->user->fresh()->money !== 5000) {
                return;
            }
        }

        $this->fail('За двадцать бросков деньги ни разу не изменились');
    }

    public function testGameNeedsMoney(): void
    {
        $this->user->update(['money' => 1]);

        $this->actingAs($this->user)
            ->get('/games/dices/go')
            ->assertSee('недостаточно средств');
    }
}
