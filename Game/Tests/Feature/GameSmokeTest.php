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
            ->assertSee(__('game::games.cannot_play'));
    }

    public function testSafeMarksEveryPosition(): void
    {
        // Пятая позиция раньше не получала метку «*»: в условии стояла чужая
        // переменная, а результат писался в четвёртую ячейку
        $this->actingAs($this->user)->get('/games/safe');

        $cipher = session('safe.cipher');

        if ($cipher === null) {
            $this->actingAs($this->user)->post('/games/safe/go', array_fill_keys(
                ['code0', 'code1', 'code2', 'code3', 'code4'],
                0,
            ));

            $cipher = session('safe.cipher');
        }

        $this->assertIsArray($cipher);

        // Сдвигаем шифр на позицию: каждая цифра есть в коде, но стоит не на своём месте
        $shifted = [$cipher[1], $cipher[2], $cipher[3], $cipher[4], $cipher[0]];

        $response = $this->actingAs($this->user)->post('/games/safe/go', [
            'code0' => $shifted[0], 'code1' => $shifted[1], 'code2' => $shifted[2],
            'code3' => $shifted[3], 'code4' => $shifted[4],
        ]);

        $hack = $response->original->getData()['hack'];

        foreach ($hack as $position => $mark) {
            $this->assertNotSame('-', $mark, "Позиция {$position} осталась без подсказки");
        }
    }

    public function testBlackjackChargesExactBet(): void
    {
        $this->user->update(['money' => 5000]);

        $this->actingAs($this->user)->post('/games/blackjack/bet', ['bet' => 55]);
        $this->assertSame(4945, $this->user->fresh()->money, 'Списана не та сумма');

        $this->actingAs($this->user)->get('/games/blackjack/game');
        $this->assertSame(4945, $this->user->fresh()->money, 'Раздача что-то списала');

        $content = (string) $this->actingAs($this->user)
            ->post('/games/blackjack/game', ['case' => 'end'])
            ->getContent();

        $money = $this->user->fresh()->money;

        if (str_contains($content, __('game::games.lost'))) {
            $this->assertSame(4945, $money, 'При проигрыше списано повторно');
            $this->assertStringContainsString(
                __('game::games.bj_lost', ['money' => plural(55, setting('moneyname'))]),
                $content,
            );
        }
    }

    public function testBlackjackGameShowsBothHands(): void
    {
        $this->actingAs($this->user)
            ->post('/games/blackjack/bet', ['bet' => 100])
            ->assertRedirect();

        // Карты банкира идут выше своих, как за столом
        $response = $this->actingAs($this->user)->get('/games/blackjack/game');

        $response->assertOk()
            ->assertSeeInOrder([
                __('game::games.bj_banker_cards'),
                __('game::games.bj_your_cards'),
            ], false);
    }

    public function testBlackjackDoesNotLoseBeforeShowdown(): void
    {
        // Банкир добирает до 17 на каждом ходу игрока, но выиграть до вскрытия не может
        for ($i = 0; $i < 30; $i++) {
            $this->user->update(['money' => 5000]);
            $this->actingAs($this->user)->post('/games/blackjack/bet', ['bet' => 100]);

            $response = $this->actingAs($this->user)->post('/games/blackjack/game', ['case' => 'take']);

            $response->assertOk()
                ->assertDontSee(__('game::games.bj_banker_blackjack'))
                ->assertDontSee(__('game::games.bj_banker_two_aces'));

            // Проигрыш тоже называет сумму
            $end = $this->actingAs($this->user)->post('/games/blackjack/game', ['case' => 'end']);

            if (str_contains((string) $end->getContent(), __('game::games.lost'))) {
                $end->assertSee(__('game::games.bj_lost', ['money' => plural(100, setting('moneyname'))]));
            }

            continue;
        }
    }

    public function testBlackjackPaysThreeToTwo(): void
    {
        // Очко набирается только добором: первая карта даёт максимум 11 очков
        for ($game = 0; $game < 80; $game++) {
            $this->user->update(['money' => 5000]);
            $this->actingAs($this->user)->post('/games/blackjack/bet', ['bet' => 100]);
            $this->actingAs($this->user)->get('/games/blackjack/game');

            for ($move = 0; $move < 10; $move++) {
                $content = $this->actingAs($this->user)
                    ->post('/games/blackjack/game', ['case' => 'take'])
                    ->getContent();

                if (str_contains((string) $content, __('game::games.bj_blackjack'))) {
                    // Ставка уже списана: 5000 - 100 + 250
                    $this->assertSame(5150, $this->user->fresh()->money);

                    // Итог партии показывается суммой, а не только словом
                    $this->assertStringContainsString(
                        __('game::games.bj_won', ['money' => plural(250, setting('moneyname'))]),
                        (string) $content,
                    );

                    return;
                }

                if (str_contains((string) $content, __('game::games.bj_bust'))) {
                    break;
                }
            }

            $this->actingAs($this->user)->post('/games/blackjack/game', ['case' => 'end']);
        }

        $this->fail('За восемьдесят партий очко ни разу не выпало');
    }

    public function testBlackjackHidesBankerCardsUntilShowdown(): void
    {
        $this->actingAs($this->user)->post('/games/blackjack/bet', ['bet' => 100]);
        $this->actingAs($this->user)->get('/games/blackjack/game')->assertSee('cards/0.png');

        $this->actingAs($this->user)
            ->post('/games/blackjack/game', ['case' => 'end'])
            ->assertOk()
            ->assertDontSee('cards/0.png');
    }
}
