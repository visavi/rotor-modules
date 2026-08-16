<?php

namespace Modules\Transfer\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Transfer\Models\Transfer;
use Tests\ModuleTestCase;

class TransferApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Transfer';

    private User $user;

    private User $recipient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('sendmoneypoint', 0);
        $this->overrideSetting('comment_text_max', 1000);

        $this->user = User::factory()->create([
            'apikey' => Str::random(32),
            'money'  => 500,
            'point'  => 100,
        ]);

        $this->recipient = User::factory()->create(['money' => 0]);
    }

    public function testTransferRequiresToken(): void
    {
        $this->postJson('/api/transfers', ['user' => $this->recipient->login, 'money' => 100])
            ->assertStatus(400);
    }

    public function testMoneyIsTransferred(): void
    {
        $this->postJson('/api/transfers', [
            'user'  => $this->recipient->login,
            'money' => 100,
            'text'  => 'За помощь',
        ], $this->headers())
            ->assertStatus(201)
            ->assertJsonPath('money', 400);

        $this->assertSame(400, (int) $this->user->fresh()->money);
        $this->assertSame(100, (int) $this->recipient->fresh()->money);

        $this->assertDatabaseHas('transfers', [
            'user_id'      => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'total'        => 100,
        ]);

        // Получателю приходит уведомление в приват
        $this->assertSame(1, $this->recipient->fresh()->getCountMessages());
    }

    public function testTransferMoreThanBalanceIsRejected(): void
    {
        $this->postJson('/api/transfers', [
            'user'  => $this->recipient->login,
            'money' => 1000,
        ], $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('money');

        $this->assertSame(500, (int) $this->user->fresh()->money);
    }

    public function testTransferToYourselfIsRejected(): void
    {
        $this->postJson('/api/transfers', [
            'user'  => $this->user->login,
            'money' => 100,
        ], $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('user');
    }

    public function testUnknownUserIsRejected(): void
    {
        $this->postJson('/api/transfers', ['user' => 'nobody', 'money' => 100], $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('user');
    }

    public function testPointsThresholdIsChecked(): void
    {
        $this->overrideSetting('sendmoneypoint', 1000);

        $this->postJson('/api/transfers', [
            'user'  => $this->recipient->login,
            'money' => 100,
        ], $this->headers())->assertStatus(422);

        $this->assertSame(500, (int) $this->user->fresh()->money);
    }

    public function testHistoryShowsBothDirections(): void
    {
        Transfer::query()->create([
            'user_id'      => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'text'         => 'Отправленный',
            'total'        => 50,
            'created_at'   => now()->subMinute(),
        ]);

        Transfer::query()->create([
            'user_id'      => $this->recipient->id,
            'recipient_id' => $this->user->id,
            'text'         => 'Полученный',
            'total'        => 20,
            'created_at'   => now(),
        ]);

        $response = $this->getJson('/api/transfers', $this->headers())
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertSame('in', $response->json('data.0.type'));
        $this->assertSame('out', $response->json('data.1.type'));
        $this->assertSame($this->recipient->login, $response->json('data.0.user'));
    }

    public function testModulePublishesThreshold(): void
    {
        $this->overrideSetting('sendmoneypoint', 25);

        $this->getJson('/api/config')
            ->assertOk()
            ->assertJsonPath('transfer.point', 25);
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->user->apikey];
    }
}
