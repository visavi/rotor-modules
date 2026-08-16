<?php

namespace Modules\Surprise\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Modules\Surprise\Models\Surprise;
use Tests\ModuleTestCase;

class SurpriseSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Surprise';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Подарок выдаётся только в новогодние дни
        CarbonImmutable::setTestNow('2027-01-01 12:00:00');

        $this->overrideSetting('bonusmoney', 0);

        Surprise::query()->delete();

        $this->user = User::factory()->create(['money' => 0, 'point' => 100, 'posrating' => 0]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function testGiftIsGiven(): void
    {
        $this->actingAs($this->user)->get('/surprise')->assertRedirect();

        $user = $this->user->fresh();

        $this->assertGreaterThanOrEqual(10000, $user->money);
        $this->assertGreaterThan(100, $user->point);
        $this->assertGreaterThan(0, $user->posrating);
        $this->assertSame($user->posrating, $user->rating);

        $this->assertDatabaseHas('surprise', ['user_id' => $this->user->id, 'year' => '2027']);

        // О подарке сообщают в приват
        $this->assertSame(1, $user->getCountMessages());
    }

    public function testGiftIsGivenOnceAYear(): void
    {
        $this->actingAs($this->user)->get('/surprise');

        $money = $this->user->fresh()->money;

        $this->actingAs($this->user)
            ->get('/surprise')
            ->assertSee(__('surprise::surprise.already_received'));

        $this->assertSame($money, $this->user->fresh()->money);
        $this->assertDatabaseCount('surprise', 1);
    }

    public function testNewcomerGetsNoPoints(): void
    {
        $this->user->update(['point' => 10]);

        $this->actingAs($this->user)->get('/surprise')->assertRedirect();

        // Баллы дают только тем, у кого их уже больше 50
        $this->assertSame(10, $this->user->fresh()->point);
    }

    public function testGiftIsClosedAfterHolidays(): void
    {
        CarbonImmutable::setTestNow('2027-02-01 12:00:00');

        $this->actingAs($this->user)
            ->get('/surprise')
            ->assertSee(__('surprise::surprise.date_receipt'));

        $this->assertDatabaseCount('surprise', 0);
    }

    public function testGuestIsRejected(): void
    {
        $this->get('/surprise')->assertForbidden();

        $this->assertDatabaseCount('surprise', 0);
    }
}
