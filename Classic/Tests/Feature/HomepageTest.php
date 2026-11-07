<?php

namespace Modules\Classic\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileSystem;
use Illuminate\Support\Facades\Route;
use Modules\Photo\Models\Photo;
use Tests\ModuleTestCase;

class HomepageTest extends ModuleTestCase
{
    protected string $moduleName = 'Classic';

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('app_installed', 1);
    }

    public function testClassicHomepageIsShownWhenSelected(): void
    {
        $this->overrideSetting('homepage', 'classic');

        $this->get('/')
            ->assertOk()
            ->assertSee('<a href="' . route('classic.recent') . '">', false)
            ->assertSee('calendar.css', false)
            ->assertDontSee('feed-container', false);
    }

    public function testEnabledModuleDoesNotReplaceFeed(): void
    {
        // Включённый модуль только предлагает главную, выбирает админ
        $this->overrideSetting('homepage', 'feed');

        $this->get('/')
            ->assertOk()
            ->assertSee('feed-container', false)
            ->assertDontSee(route('classic.recent'), false)
            ->assertDontSee('calendar.css', false);
    }

    public function testRecentPhotosShowSquareThumbnailsWithoutExtraQueries(): void
    {
        $publicPath = storage_path('framework/testing/public-' . uniqid());
        $this->app->usePublicPath($publicPath);
        $this->beforeApplicationDestroyed(static fn () => FileSystem::deleteDirectory($publicPath));

        Relation::morphMap([Photo::$morphName => Photo::class]);
        // Маршруты Photo в тесте Classic не подключены, ссылке на фото нужен только адрес
        Route::get('/photos/{id}', static fn () => '')->name('photos.view');
        app('router')->getRoutes()->refreshNameLookups();
        FileSystem::ensureDirectoryExists(public_path('uploads/photos'));
        $user = User::factory()->create();

        foreach (['a', 'b'] as $name) {
            $image = UploadedFile::fake()->image($name . '.jpg', 1200, 900);
            FileSystem::copy($image->getPathname(), public_path('uploads/photos/' . $name . '.jpg'));

            File::factory()->create([
                'relate_type' => Photo::$morphName,
                'relate_id'   => Photo::factory()->create(['user_id' => $user->id])->id,
                'path'        => '/uploads/photos/' . $name . '.jpg',
                'extension'   => 'jpg',
                'user_id'     => $user->id,
            ]);
        }

        DB::enableQueryLog();
        $html = (string) recentPhotos();

        // Миниатюра 100px — квадратное превью, а не оригинал
        $this->assertStringContainsString('src="/uploads/thumbnails/150/photos/a.webp"', $html);
        $this->assertStringContainsString('src="/uploads/thumbnails/150/photos/b.webp"', $html);

        // Фото и их файлы — два запроса, а не запрос файлов на каждое фото
        $this->assertCount(2, DB::getQueryLog());
    }
}
