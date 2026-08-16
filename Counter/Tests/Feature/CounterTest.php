<?php

namespace Modules\Counter\Tests\Feature;

use App\Support\Registry;
use Illuminate\Support\Facades\Cache;
use Modules\Counter\Models\Counter;
use Modules\Counter\Models\Counter24;
use Modules\Counter\Models\Counter31;
use Modules\Counter\Services\CounterStatistic;
use Tests\ModuleTestCase;

class CounterTest extends ModuleTestCase
{
    protected string $moduleName = 'Counter';

    protected function setUp(): void
    {
        parent::setUp();

        Counter::query()->delete();
        Counter24::query()->delete();
        Counter31::query()->delete();
        Cache::forget('counter');
    }

    public function testHostAndHitsAreCounted(): void
    {
        $this->counter();

        (new CounterStatistic())->save(true, 3);

        $counter = Counter::query()->first();

        $this->assertSame(1, $counter->allhosts);
        $this->assertSame(1, $counter->dayhosts);
        $this->assertSame(1, $counter->hosts24);
        $this->assertSame(3, $counter->allhits);
        $this->assertSame(3, $counter->dayhits);
        $this->assertSame(3, $counter->hits24);
    }

    public function testRepeatVisitAddsOnlyHits(): void
    {
        $this->counter(['allhosts' => 5, 'dayhosts' => 2, 'hosts24' => 1]);

        (new CounterStatistic())->save(false, 1);

        $counter = Counter::query()->first();

        $this->assertSame(5, $counter->allhosts);
        $this->assertSame(2, $counter->dayhosts);
        $this->assertSame(1, $counter->allhits);
    }

    public function testNewDayIsArchived(): void
    {
        $yesterday = now()->subDay();

        $this->counter([
            'period'   => $yesterday->format('Y-m-d H:00:00'),
            'dayhosts' => 40,
            'dayhits'  => 90,
        ]);

        (new CounterStatistic())->save(true, 1);

        // Вчерашние итоги уходят в архив по дням, суточные счётчики начинаются заново
        $this->assertDatabaseHas('counters31', [
            'period' => $yesterday->format('Y-m-d 00:00:00'),
            'hosts'  => 40,
            'hits'   => 90,
        ]);

        $counter = Counter::query()->first();

        $this->assertSame(1, $counter->dayhosts);
        $this->assertSame(1, $counter->dayhits);
    }

    public function testNewHourIsArchived(): void
    {
        $hourAgo = now()->subHour();

        $this->counter([
            'period'  => $hourAgo->format('Y-m-d H:00:00'),
            'hosts24' => 7,
            'hits24'  => 15,
        ]);

        (new CounterStatistic())->save(false, 2);

        $this->assertDatabaseHas('counters24', [
            'period' => $hourAgo->format('Y-m-d H:00:00'),
            'hosts'  => 7,
            'hits'   => 15,
        ]);

        $counter = Counter::query()->first();

        $this->assertSame(now()->format('Y-m-d H:00:00'), $counter->period);
        $this->assertSame(0, $counter->hosts24);
        $this->assertSame(2, $counter->hits24);
    }

    public function testEmptyCounterDoesNotBreakSave(): void
    {
        // Строки счётчика может не быть на свежей установке
        (new CounterStatistic())->save(true, 1);

        $this->assertDatabaseCount('counters', 0);
    }

    public function testStatisticHookIsRegistered(): void
    {
        $this->counter();

        // Счётчик наполняет ядро — через хук onSaveStatistic
        foreach (Registry::$onSaveStatistic as $handler) {
            $handler(true, 2);
        }

        $counter = Counter::query()->first();

        $this->assertSame(1, $counter->allhosts);
        $this->assertSame(2, $counter->allhits);
    }

    public function testStatisticPageIsOpen(): void
    {
        $this->counter();

        $this->get('/counters')
            ->assertOk()
            ->assertSee(__('counter::counters.hosts_total'))
            ->assertSee(__('counter::counters.hits_day'));
    }

    public function testVisitIsCountedByCore(): void
    {
        $this->counter();

        // Ядро дёргает хук на каждом запросе — счётчик растёт сам
        $this->get('/counters')->assertOk();

        $counter = Counter::query()->first();

        $this->assertSame(1, $counter->allhosts);
        $this->assertGreaterThan(0, $counter->allhits);
    }

    private function counter(array $attributes = []): Counter
    {
        return Counter::query()->create([
            'period'   => now()->format('Y-m-d H:00:00'),
            'allhosts' => 0,
            'allhits'  => 0,
            'dayhosts' => 0,
            'dayhits'  => 0,
            'hosts24'  => 0,
            'hits24'   => 0,
            ...$attributes,
        ]);
    }
}
