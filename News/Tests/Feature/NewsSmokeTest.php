<?php

namespace Modules\News\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\News\Models\News;
use Tests\ModuleTestCase;

class NewsSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'News';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([News::$morphName => News::class]);

        $this->user = User::factory()->create();
    }

    public function testIndex(): void
    {
        $this->get(route('news.index'))->assertOk();
    }

    public function testView(): void
    {
        $news = News::query()->create([
            'title'      => 'Test news',
            'text'       => 'Test news text',
            'user_id'    => $this->user->id,
            'created_at' => SITETIME,
        ]);

        $this->get(route('news.view', ['id' => $news->id]))->assertOk();
    }
}
