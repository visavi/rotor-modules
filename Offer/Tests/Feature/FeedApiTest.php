<?php

namespace Modules\Offer\Tests\Feature;

use App\Classes\Registry;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Offer\Models\Offer;
use Tests\ModuleTestCase;

class FeedApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Offer';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Offer::$morphName => Offer::class]);

        // Ленту регистрирует ModuleServiceProvider, в тестах модуль поднимается вручную
        $config = require base_path('modules/Offer/module.php');
        Registry::feed(Offer::class, $config['models'][Offer::class]['feed']);

        $this->overrideSetting('feed_offers_show', 1);
        $this->overrideSetting('feed_offers_rating', -5);
        $this->overrideSetting('feed_per_page', 20);
        $this->overrideSetting('feed_cache_time', 0);

        $this->user = User::factory()->create();
    }

    public function testFeedReturnsOfferMedia(): void
    {
        $offer = $this->createOfferInFeed();

        File::query()->create([
            'relate_id'   => $offer->id,
            'relate_type' => Offer::$morphName,
            'path'        => '/uploads/offers/screen.jpg',
            'name'        => 'screen.jpg',
            'size'        => 1024,
            'extension'   => 'jpg',
            'mime_type'   => 'image/jpeg',
            'user_id'     => $this->user->id,
        ]);

        $response = $this->get('/api/feed');

        $response->assertOk();
        $response->assertJsonPath('data.0.type', Offer::$morphName);
        // Ключа files нет в конфиге ленты — связь подгружает ядро
        $response->assertJsonCount(1, 'data.0.media');
        $response->assertJsonPath('data.0.media.0.name', 'screen.jpg');
        $response->assertJsonPath('data.0.offer_type', Offer::ISSUE);
    }

    private function createOfferInFeed(): Offer
    {
        // Запись сама попадает в ленту через FeedableTrait
        return Offer::query()->create([
            'type'       => Offer::ISSUE,
            'title'      => 'Test issue',
            'text'       => 'Test issue text',
            'user_id'    => $this->user->id,
            'rating'     => 1,
            'status'     => Offer::WAIT,
            'created_at' => now(),
        ]);
    }
}
