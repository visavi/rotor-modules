<?php

namespace Modules\Classic\Tests\Feature;

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
}
