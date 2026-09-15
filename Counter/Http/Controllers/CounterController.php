<?php

declare(strict_types=1);

namespace Modules\Counter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Counter\Models\Counter24;
use Modules\Counter\Models\Counter31;

class CounterController extends Controller
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $count = statsCounter();
        $online = statsOnline();

        // Активный период счётчика: пока он не закрыт, его итогов нет в архиве
        $hour = $count['period'] ?? now()->format('Y-m-d H:00:00');
        $today = date('Y-m-d 00:00:00', strtotime($hour));

        $counts31 = [];
        $counters = Counter31::query()
            ->where('period', '>=', now()->subDays(29)->format('Y-m-d 00:00:00'))
            ->orderByDesc('period')
            ->get()
            ->keyBy('period');

        for ($i = 0; $i < 30; $i++) {
            $curDate = now()->subDays($i)->format('Y-m-d 00:00:00');

            $cnt = $counters->get($curDate);

            // Текущие сутки в архив ещё не ушли, данные берутся из активного счётчика
            $counts31['hits'][] = $curDate === $today ? (int) ($count['dayhits'] ?? 0) : (int) ($cnt->hits ?? 0);
            $counts31['hosts'][] = $curDate === $today ? (int) ($count['dayhosts'] ?? 0) : (int) ($cnt->hosts ?? 0);
            $counts31['labels'][] = date('M j', strtotime($curDate));
        }

        $counts24 = [];
        $counters = Counter24::query()
            ->where('period', '>=', now()->subHours(23)->format('Y-m-d H:00:00'))
            ->orderByDesc('period')
            ->get()
            ->keyBy('period');

        for ($i = 0; $i < 24; $i++) {
            $curHour = now()->subHours($i)->format('Y-m-d H:00:00');

            $cnt = $counters->get($curHour);

            // Текущий час в архив ещё не ушёл, данные берутся из активного счётчика
            $counts24['hits'][] = $curHour === $hour ? (int) ($count['hits24'] ?? 0) : (int) ($cnt->hits ?? 0);
            $counts24['hosts'][] = $curHour === $hour ? (int) ($count['hosts24'] ?? 0) : (int) ($cnt->hosts ?? 0);
            $counts24['labels'][] = date('H', strtotime($curHour));
        }

        return view('counter::index', compact('count', 'online', 'counts24', 'counts31'));
    }
}
