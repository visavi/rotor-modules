<?php

declare(strict_types=1);

namespace Modules\Classic\Classes;

use App\Models\Module;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\News\Models\News;

class Calendar
{
    /**
     * Возвращает календарь
     *
     * @param string|null $period Месяц в формате Y-m (по умолчанию текущий)
     */
    public function getCalendar(?string $period = null): View
    {
        [$curDay, $curMonth, $curYear] = array_map('intval', explode('.', dateFixed(now(), 'j.n.Y', true)));

        $month = $curMonth;
        $year = $curYear;

        if ($period !== null && preg_match('/^(\d{4})-(\d{1,2})$/', $period, $match)) {
            $year = (int) $match[1];
            $month = min(12, max(1, (int) $match[2]));
        }

        $startMonth = (int) mktime(0, 0, 0, $month, 1, $year);
        $endMonth = (int) strtotime('+1 month', $startMonth);

        // Подсветка текущего дня только если открыт текущий месяц
        $currentDay = ($month === $curMonth && $year === $curYear) ? $curDay : 0;

        $newsIds = [];
        if (isset(Module::getEnabledModules()['News'])) {
            $news = News::query()
                ->where('created_at', '>=', date('Y-m-d H:i:s', $startMonth))
                ->where('created_at', '<', date('Y-m-d H:i:s', $endMonth))
                ->get();

            foreach ($news as $data) {
                $newsIds[(int) dateFixed($data->created_at, 'j')] = $data->id;
            }
        }

        $calendar = $this->makeCalendar($month, $year);
        $prev = date('Y-m', (int) strtotime('-1 month', $startMonth));
        $next = date('Y-m', $endMonth);

        // Именительный падеж месяца из локали Carbon (ua → uk), без отдельных переводов
        $locale = app()->getLocale();
        $monthLabel = Str::ucfirst(
            Date::create($year, $month, 1)->locale($locale === 'ua' ? 'uk' : $locale)->isoFormat('MMMM YYYY')
        );

        return view('classic::_calendar', compact('calendar', 'monthLabel', 'currentDay', 'newsIds', 'prev', 'next'));
    }

    /**
     * Формирует календарь
     */
    protected function makeCalendar(int $month, int $year): array
    {
        $date = date('w', mktime(0, 0, 0, $month, 1, $year));

        if ($date === '0') {
            $date = 7;
        }

        $n = -($date - 2);
        $cal = [];
        for ($y = 0; $y < 6; $y++) {
            $row = [];
            $notEmpty = false;
            for ($x = 0; $x < 7; $x++, $n++) {
                if (checkdate($month, $n, $year)) {
                    $row[] = $n;
                    $notEmpty = true;
                } else {
                    $row[] = null;
                }
            }

            if (! $notEmpty) {
                break;
            }

            $cal[] = $row;
        }

        return $cal;
    }
}
