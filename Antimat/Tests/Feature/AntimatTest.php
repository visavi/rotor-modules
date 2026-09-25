<?php

namespace Modules\Antimat\Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use App\Support\Registry;
use Illuminate\Support\Facades\DB;
use Modules\Antimat\Models\Antimat;
use Tests\ModuleTestCase;

class AntimatTest extends ModuleTestCase
{
    protected string $moduleName = 'Antimat';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['login' => 'admin_antimat']);
    }

    public function testFilterReplacesWords(): void
    {
        Antimat::query()->create(['string' => 'xxx']);

        $this->assertSame('test', Registry::filterText('test'));
        $this->assertSame('test***test', Registry::filterText('testxxxtest'));
        $this->assertSame('тест***тест***', Registry::filterText('тестxxxтестxxx'));
        $this->assertSame('***', Registry::filterText('XXX'));
    }

    public function testFilterReplacesLongerWordsFirst(): void
    {
        Antimat::query()->create(['string' => 'xx']);
        Antimat::query()->create(['string' => 'xxxx']);

        $this->assertSame('a***b', Registry::filterText('axxxxb'));
    }

    public function testFilterWithoutWordsKeepsText(): void
    {
        $this->assertSame('текст', Registry::filterText('текст'));
    }

    public function testFilterEscapesRegexCharacters(): void
    {
        Antimat::query()->create(['string' => 'a.b']);

        $this->assertSame('axb ***', Registry::filterText('axb a.b'));
    }

    public function testWholeWordMode(): void
    {
        Antimat::query()->create(['string' => 'бяка']);

        $this->overrideSetting('antimat_whole_word', 1);
        $this->assertSame('бякалка ***', Registry::filterText('бякалка бяка'));

        $this->overrideSetting('antimat_whole_word', 0);

        $this->assertSame('***лка ***', Registry::filterText('бякалка бяка'));
    }

    public function testCustomReplacement(): void
    {
        Antimat::query()->create(['string' => 'бяка']);

        $this->overrideSetting('antimat_replace', '[цензура]');
        $this->assertSame('ну [цензура]', Registry::filterText('ну бяка'));

        // Спецсимволы замены не должны работать как обратные ссылки
        $this->overrideSetting('antimat_replace', '$0\\1');
        $this->assertSame('ну $0\\1', Registry::filterText('ну бяка'));

        // Пустая настройка — замена по умолчанию
        $this->overrideSetting('antimat_replace', '');
        $this->assertSame('ну ***', Registry::filterText('ну бяка'));
    }

    public function testModelTextFilteredOnReadOnly(): void
    {
        Antimat::query()->create(['string' => 'бяка']);

        $comment = Comment::query()->create([
            'relate_type' => 'test',
            'relate_id'   => 1,
            'user_id'     => $this->admin->id,
            'text'        => '<p>ну бяка</p>',
            'ip'          => '127.0.0.1',
            'brow'        => 'test',
        ]);

        $this->assertSame('<p>ну ***</p>', $comment->fresh()->text);
        $this->assertSame('<p>ну бяка</p>', DB::table('comments')->where('id', $comment->id)->value('text'));
    }

    public function testCacheResetsWhenListChanges(): void
    {
        $this->assertSame('бяка', Registry::filterText('бяка'));

        $word = Antimat::query()->create(['string' => 'бяка']);
        $this->assertSame('***', Registry::filterText('бяка'));

        $word->delete();
        $this->assertSame('бяка', Registry::filterText('бяка'));

        Antimat::query()->create(['string' => 'бяка']);
        $boss = User::factory()->boss()->create(['login' => 'boss_cache']);
        $this->actingAs($boss)->post('/admin/antimat/clear');

        $this->assertSame('бяка', Registry::filterText('бяка'));
    }

    public function testSettingsRequireBoss(): void
    {
        // Настройки модулей — только для владельца: сохранение пишет любые ключи из sets[]
        $this->actingAs($this->admin)
            ->get('/admin/antimat-settings')
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->post('/admin/antimat-settings', ['sets' => ['closedsite' => 1]])
            ->assertForbidden();

        $this->assertDatabaseMissing('settings', ['name' => 'closedsite', 'value' => '1']);
    }

    public function testSettingsPageOpens(): void
    {
        $this->actingAs($this->boss())
            ->get('/admin/antimat-settings')
            ->assertOk()
            ->assertSee('antimat_whole_word');
    }

    public function testSettingsSaveEmptyReplacement(): void
    {
        $this->actingAs($this->boss())
            ->post('/admin/antimat-settings', ['sets' => ['antimat_replace' => '', 'antimat_whole_word' => 1]])
            ->assertRedirect(route('antimat.settings'));

        $this->assertDatabaseHas('settings', ['name' => 'antimat_replace', 'value' => '']);
        $this->assertDatabaseHas('settings', ['name' => 'antimat_whole_word', 'value' => '1']);
    }

    public function testIndexRequiresModer(): void
    {
        $user = User::factory()->create(['login' => 'user_antimat']);

        $this->actingAs($user)
            ->get('/admin/antimat')
            ->assertForbidden();
    }

    public function testIndexShowsWords(): void
    {
        Antimat::query()->create(['string' => 'тестослово']);

        $this->actingAs($this->admin)
            ->get('/admin/antimat')
            ->assertOk()
            ->assertSee('тестослово');
    }

    public function testAdminPanelShowsTile(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee(route('admin.antimat.index'));
    }

    public function testAddWord(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/antimat', ['word' => 'тестослово']);

        $response->assertRedirect(route('admin.antimat.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('antimat', ['string' => 'тестослово']);
    }

    public function testAddWordIsLowercased(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/antimat', ['word' => 'ТестоСлово']);

        // Сравниваем в PHP: у таблицы case-insensitive collation,
        // assertDatabaseHas прошёл бы и на нетронутом регистре
        $this->assertSame('тестослово', Antimat::query()->value('string'));
    }

    public function testAddEmptyWordFails(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/antimat', ['word' => '']);

        $response->assertSessionHasErrors();

        $this->assertDatabaseCount('antimat', 0);
    }

    public function testAddDuplicateWordFails(): void
    {
        Antimat::query()->create(['string' => 'тестослово']);

        $response = $this->actingAs($this->admin)
            ->from('/admin/antimat')
            ->post('/admin/antimat', ['word' => 'тестослово']);

        $response->assertRedirect('/admin/antimat');
        $response->assertSessionHasErrors();

        $this->assertDatabaseCount('antimat', 1);

        $this->actingAs($this->admin)
            ->get('/admin/antimat')
            ->assertSee(__('antimat::antimat.word_listed'));
    }

    public function testDeleteWord(): void
    {
        $word = Antimat::query()->create(['string' => 'тестослово']);

        $response = $this->actingAs($this->admin)
            ->delete('/admin/antimat/delete', ['id' => $word->id]);

        $response->assertRedirect(route('admin.antimat.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('antimat', ['id' => $word->id]);
    }

    public function testDeleteMissingWordFails(): void
    {
        $this->actingAs($this->admin)
            ->delete('/admin/antimat/delete', ['id' => 999999])
            ->assertRedirect(route('admin.antimat.index'))
            ->assertSessionHasErrors();
    }

    public function testClearAsBoss(): void
    {
        Antimat::query()->create(['string' => 'тестослово']);

        $boss = User::factory()->boss()->create(['login' => 'boss_antimat']);

        $response = $this->actingAs($boss)
            ->post('/admin/antimat/clear');

        $response->assertRedirect(route('admin.antimat.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('antimat', 0);
    }

    public function testClearAsAdminIsForbidden(): void
    {
        Antimat::query()->create(['string' => 'тестослово']);

        $this->actingAs($this->admin)
            ->post('/admin/antimat/clear')
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('antimat', 1);
    }

    public function testAddTooLongWordFails(): void
    {
        // Колонка string(100): длиннее строгий MySQL не пропустит
        $response = $this->actingAs($this->admin)
            ->from('/admin/antimat')
            ->post('/admin/antimat', ['word' => str_repeat('щ', 101)]);

        $response->assertRedirect('/admin/antimat');
        $response->assertSessionHasErrors();

        $this->assertDatabaseCount('antimat', 0);
    }

    public function testSettingsTooLongValueFails(): void
    {
        // Общий контроллер настроек модулей: settings.value — string(255)
        $response = $this->actingAs($this->boss())
            ->from('/admin/antimat-settings')
            ->post('/admin/antimat-settings', ['sets' => ['antimat_replace' => str_repeat('*', 256)]]);

        $response->assertRedirect('/admin/antimat-settings');
        $response->assertSessionHasErrors('sets[antimat_replace]');

        $this->assertDatabaseMissing('settings', ['name' => 'antimat_replace', 'value' => str_repeat('*', 256)]);
    }

    private function boss(): User
    {
        return User::factory()->boss()->create(['login' => 'boss_antimat']);
    }
}
