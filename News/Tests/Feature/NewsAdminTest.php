<?php

namespace Modules\News\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use Modules\News\Models\News;
use Tests\ModuleTestCase;

class NewsAdminTest extends ModuleTestCase
{
    protected string $moduleName = 'News';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([News::$morphName => News::class]);

        $this->overrideSetting('news_title_min', 5);
        $this->overrideSetting('news_title_max', 50);
        $this->overrideSetting('news_text_min', 10);
        $this->overrideSetting('news_text_max', 1000);
        $this->overrideSetting('postnews', 10);

        $this->admin = User::factory()->create(['level' => User::ADMIN]);
    }

    public function testNewsIsPublishedWithUploadedFiles(): void
    {
        $file = File::factory()->create([
            'relate_id'   => 0,
            'relate_type' => News::$morphName,
            'path'        => '/uploads/news/doc.pdf',
            'name'        => 'doc.pdf',
            'size'        => 1024,
            'extension'   => 'pdf',
            'user_id'     => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/news/create')
            ->assertOk()
            ->assertSee('doc.pdf');

        $this->actingAs($this->admin)
            ->post('/admin/news/create', [
                'title'  => 'Первая новость',
                'text'   => '<p>Текст первой новости</p>',
                'closed' => 0,
                'pinned' => 1,
            ])
            ->assertSessionHas('success');

        $news = News::query()->where('title', 'Первая новость')->firstOrFail();

        $this->assertSame($this->admin->id, $news->user_id);
        $this->assertSame(1, (int) $news->pinned);
        $this->assertSame($news->id, $file->fresh()->relate_id);
    }

    public function testMarkupIsNotCountedInLength(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/news/create', [
                'title' => 'Первая новость',
                'text'  => '<p><strong><em>Коротко</em></strong></p>',
            ])
            ->assertSessionHasErrors('text');

        $this->assertDatabaseCount('news', 0);
    }

    public function testShortTitleIsRejected(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/news/create', ['title' => 'Ой', 'text' => 'Текст первой новости'])
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('news', 0);
    }

    public function testNewsIsEdited(): void
    {
        $news = $this->createNews();

        // Закреплённую новость и дату последней читает тема Classic
        Cache::put('pinnedNews', 'старый виджет');
        Cache::put('statNewsDate', 'старая дата');

        $this->actingAs($this->admin)
            ->get('/admin/news/' . $news->id . '/edit')
            ->assertOk()
            ->assertSee('Текст новости');

        $this->actingAs($this->admin)
            ->post('/admin/news/' . $news->id . '/edit', [
                'title'  => 'Исправленная новость',
                'text'   => 'Исправленный текст новости',
                'closed' => 1,
            ])
            ->assertRedirect(route('news.view', ['id' => $news->id]));

        $news->refresh();
        $this->assertSame('Исправленная новость', $news->title);
        $this->assertTrue($news->closed);
        $this->assertFalse(Cache::has('pinnedNews'));
        $this->assertFalse(Cache::has('statNewsDate'));
    }

    public function testEditErrorReturnsToForm(): void
    {
        $news = $this->createNews();
        $form = route('admin.news.edit', ['id' => $news->id, 'page' => 2]);

        $this->actingAs($this->admin)
            ->from($form)
            ->post('/admin/news/' . $news->id . '/edit', ['title' => 'Исправленная новость', 'text' => ''])
            ->assertRedirect($form)
            ->assertSessionHasErrors('text');

        $this->assertSame('Заголовок новости', $news->fresh()->title);
    }

    public function testNewsIsDeleted(): void
    {
        $news = $this->createNews();

        $this->actingAs($this->admin)
            ->delete('/admin/news/' . $news->id . '/delete?page=3')
            ->assertRedirect(route('admin.news.index', ['page' => 3]));

        $this->assertNull($news->fresh());
    }

    public function testOnlyBossRecounts(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/news/restatement')
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['level' => User::BOSS]))
            ->post('/admin/news/restatement')
            ->assertSessionHas('success');
    }

    private function createNews(): News
    {
        return News::factory()->create([
            'user_id'    => $this->admin->id,
            'title'      => 'Заголовок новости',
            'text'       => 'Текст новости',
            'closed'     => 0,
            'pinned'     => 0,
            'created_at' => now(),
        ]);
    }
}
