<?php

namespace Modules\Photo\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Photo\Models\Photo;
use Tests\ModuleTestCase;

class PhotoSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Photo';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Photo::$morphName => Photo::class]);

        $this->user = User::factory()->create();
    }

    public function testNewComments(): void
    {
        $this->assertSame(url('/photos/comments'), route('photos.new-comments'));
        $this->get(route('photos.new-comments'))->assertOk();
    }

    public function testUserPagesAreClosedForGuests(): void
    {
        // Страницы пользователя только авторизованным — гостю 403
        $routes = ['photos.user-albums', 'photos.user-comments', 'photos.albums'];

        foreach ($routes as $route) {
            $this->get(route($route, ['user' => $this->user->login]))->assertForbidden();
        }

        $this->actingAs($this->user);

        foreach ($routes as $route) {
            $this->get(route($route, ['user' => $this->user->login]))->assertOk();
        }
    }
}
