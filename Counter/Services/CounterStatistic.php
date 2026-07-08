<?php

declare(strict_types=1);

namespace Modules\Counter\Services;

use Illuminate\Support\Facades\DB;
use Modules\Counter\Models\Counter;
use Modules\Counter\Models\Counter24;
use Modules\Counter\Models\Counter31;

class CounterStatistic
{
    /**
     * Сохраняет статистику посещений
     */
    public function save(bool $newHost, int $hits): void
    {
        $period = now()->format('Y-m-d H:00:00');
        $day = now()->format('Y-m-d 00:00:00');

        $counter = Counter::query()->first();

        if (! $counter) {
            return;
        }

        if (date('Y-m-d 00:00:00', strtotime($counter->period)) !== $day) {
            Counter31::query()->insertOrIgnore([
                'period' => $period,
                'hosts'  => $counter->dayhosts,
                'hits'   => $counter->dayhits,
            ]);

            $counter->update([
                'dayhosts' => 0,
                'dayhits'  => 0,
            ]);
        }

        if ($counter->period !== $period) {
            Counter24::query()->insertOrIgnore([
                'period' => $period,
                'hosts'  => $counter->hosts24,
                'hits'   => $counter->hits24,
            ]);

            $counter->update([
                'period'  => $period,
                'hosts24' => 0,
                'hits24'  => 0,
            ]);
        }

        $hostsUpdate = [];
        if ($newHost) {
            $hostsUpdate = [
                'allhosts' => DB::raw('allhosts + 1'),
                'dayhosts' => DB::raw('dayhosts + 1'),
                'hosts24'  => DB::raw('hosts24 + 1'),
            ];
        }

        $hitsUpdate = [
            'allhits' => DB::raw('allhits + ' . $hits),
            'dayhits' => DB::raw('dayhits + ' . $hits),
            'hits24'  => DB::raw('hits24 + ' . $hits),
        ];

        $counter->update(array_merge($hostsUpdate, $hitsUpdate));
    }
}
