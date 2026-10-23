<?php

namespace Modules\Share\Tests\Feature;

use App\Models\User;
use App\Support\Hook;
use Tests\ModuleTestCase;

class ShareTest extends ModuleTestCase
{
    protected string $moduleName = 'Share';

    protected function setUp(): void
    {
        parent::setUp();

        foreach (array_keys(config('share.networks')) as $key) {
            $this->overrideSetting('share_' . $key, 0);
        }

        $this->overrideSetting('share_copy', 0);
    }

    public function testNothingIsRenderedWhenAllDisabled(): void
    {
        $html = Hook::call('share', ['url' => 'https://example.com/a', 'title' => 'Запись']);

        $this->assertStringNotContainsString('fa-share-nodes', $html);
        $this->assertStringNotContainsString('<a ', $html);
    }

    public function testOnlyEnabledNetworksAreRendered(): void
    {
        $this->overrideSetting('share_telegram', 1);

        $html = Hook::call('share', ['url' => 'https://example.com/a', 'title' => 'Запись']);

        $this->assertStringContainsString('https://t.me/share/url', $html);
        $this->assertStringNotContainsString('vk.com', $html);
        $this->assertStringNotContainsString('data-copy', $html);
    }

    public function testUrlAndTitleAreEncoded(): void
    {
        $this->overrideSetting('share_telegram', 1);

        $html = Hook::call('share', ['url' => 'https://example.com/a?b=1&c=2', 'title' => 'Он & <она>']);

        // Параметры кодируются целиком: & из адреса и заголовка не рвёт ссылку соцсети
        $this->assertStringContainsString(
            'url=' . rawurlencode('https://example.com/a?b=1&c=2') . '&amp;text=' . rawurlencode('Он & <она>'),
            $html,
        );
        $this->assertStringNotContainsString('<она>', $html);
    }

    public function testCopyLinkIsRendered(): void
    {
        $this->overrideSetting('share_copy', 1);

        $this->assertStringContainsString(
            'data-copy="https://example.com/a"',
            Hook::call('share', ['url' => 'https://example.com/a', 'title' => 'Запись']),
        );
    }

    public function testSettingsAreBossOnly(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('share.settings'))
            ->assertForbidden();

        $this->actingAs(User::factory()->boss()->create())
            ->get(route('share.settings'))
            ->assertOk()
            ->assertSee('name="sets[share_telegram]"', false);
    }

    public function testSettingsSaveOnlyOwnKeys(): void
    {
        /** @var array<string, mixed> $networks */
        $networks = config('share.networks');
        $sets = array_fill_keys(
            array_map(static fn (string $key): string => 'share_' . $key, [...array_keys($networks), 'copy']),
            0,
        );

        $this->actingAs(User::factory()->boss()->create())
            ->post(route('share.settings.update'), ['sets' => ['share_vk' => 1, 'title' => 'Чужой ключ'] + $sets])
            ->assertRedirect(route('share.settings'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, setting('share_vk'));
        $this->assertNotSame('Чужой ключ', setting('title'));
    }

    public function testSettingsRejectNonBoolean(): void
    {
        $this->actingAs(User::factory()->boss()->create())
            ->post(route('share.settings.update'), ['sets' => ['share_vk' => 'yes']])
            ->assertSessionHasErrors('sets.share_vk');
    }
}
