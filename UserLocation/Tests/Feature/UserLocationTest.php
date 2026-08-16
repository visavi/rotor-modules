<?php

namespace Modules\UserLocation\Tests\Feature;

use App\Models\User;
use App\Support\Hook;
use Modules\UserLocation\Models\UserLocation;
use Tests\ModuleTestCase;

class UserLocationTest extends ModuleTestCase
{
    protected string $moduleName = 'UserLocation';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        UserLocation::query()->delete();

        $this->user = User::factory()->create();
    }

    public function testVisitIsTracked(): void
    {
        $this->actingAs($this->user)->get('/locations')->assertOk();

        $location = UserLocation::query()->where('user_id', $this->user->id)->first();

        $this->assertNotNull($location);
        $this->assertSame('/locations', $location->path);
        $this->assertNotEmpty($location->title);
        $this->assertNotEmpty($location->ip);
    }

    public function testQueryStringIsKept(): void
    {
        $this->actingAs($this->user)->get('/locations?page=2')->assertOk();

        $this->assertSame('/locations?page=2', UserLocation::query()->value('path'));
    }

    public function testOnlyOneRecordPerUser(): void
    {
        $this->actingAs($this->user)->get('/locations')->assertOk();
        $this->actingAs($this->user)->get('/locations?page=2')->assertOk();

        // На пользователя хранится только последняя страница
        $this->assertDatabaseCount('user_locations', 1);
        $this->assertSame('/locations?page=2', UserLocation::query()->value('path'));
    }

    public function testGuestIsNotTracked(): void
    {
        $this->get('/locations')->assertOk();

        $this->assertDatabaseCount('user_locations', 0);
    }

    public function testAdminPagesAreNotTracked(): void
    {
        $admin = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($admin)->get('/admin');

        $this->assertDatabaseCount('user_locations', 0);
    }

    public function testPageListsVisitors(): void
    {
        $this->location(['title' => 'Форум сайта']);

        $this->get('/locations')
            ->assertOk()
            ->assertSee('Форум сайта');
    }

    public function testAdminCardShowsLastPage(): void
    {
        $this->location(['title' => 'Форум сайта', 'ip' => '127.0.0.1']);

        // Карточку в админке рисует хук adminUserCard
        $html = Hook::call('adminUserCard', $this->user);

        $this->assertStringContainsString('Форум сайта', $html);
        $this->assertStringContainsString('127.0.0.1', $html);
    }

    public function testAdminCardIsEmptyWithoutVisits(): void
    {
        // В выводе остаётся только маркер самого хука, ссылки на страницу нет
        $this->assertStringNotContainsString('<a href', Hook::call('adminUserCard', $this->user));
    }

    private function location(array $attributes = []): UserLocation
    {
        return UserLocation::query()->create([
            'user_id'    => $this->user->id,
            'path'       => '/forums',
            'title'      => 'Форум',
            'ip'         => '127.0.0.1',
            'brow'       => 'Chrome 120',
            'created_at' => now(),
            ...$attributes,
        ]);
    }
}
