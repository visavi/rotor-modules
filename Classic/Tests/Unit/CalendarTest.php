<?php

namespace Modules\Classic\Tests\Unit;

use Modules\Classic\Classes\Calendar;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Calendar::class)]
class CalendarTest extends TestCase
{
    private Calendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->addNamespace('classic', base_path('modules/Classic/resources/views'));

        $this->calendar = new Calendar();
    }

    public function testMakeCalendar(): void
    {
        $grid = $this->callMethod($this->calendar, 'makeCalendar', [1, 1980]);

        self::assertIsArray($grid);
        self::assertCount(5, $grid);
        self::assertNull($grid[0][0]);
        self::assertSame(1, $grid[0][1]);
        self::assertNull($grid[4][5]);
    }

    public function testGetCalendar(): void
    {
        $calendar = preg_replace('/\s+/', '', (string) $this->calendar->getCalendar('1980-01'));

        // Заголовок месяца (именительный падеж из локали Carbon)
        self::assertStringContainsString('Январь1980', $calendar);

        // Стрелки навигации на соседние месяцы
        self::assertStringContainsString('calendar=1979-12', $calendar);
        self::assertStringContainsString('calendar=1980-02', $calendar);

        // Все дни месяца присутствуют
        self::assertStringContainsString('>31</div>', $calendar);

        // День не подсвечен как текущий (1980 — не текущий месяц)
        self::assertStringNotContainsString('bg-danger', $calendar);
    }
}
