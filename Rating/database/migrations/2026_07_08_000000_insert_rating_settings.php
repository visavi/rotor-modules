<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Шаблон уведомления по локалям: модуль на момент установки ещё не активен,
     * его переводы не загружены, поэтому строки заданы литералами.
     */
    private array $notices = [
        'ru' => [
            'name' => 'Изменение репутации',
            'text' => '<p>Пользователь %login% поставил вам %vote%! (Ваш рейтинг: %rating%)</p><p>Комментарий: %comment%</p>',
        ],
        'ua' => [
            'name' => 'Зміна репутації',
            'text' => '<p>Користувач %login% поставив вам %vote%! (Ваш рейтинг: %rating%)</p><p>Коментар: %comment%</p>',
        ],
        'en' => [
            'name' => 'Reputation change',
            'text' => '<p>User %login% gave you %vote%! (Your rating: %rating%)</p><p>Comment: %comment%</p>',
        ],
    ];

    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['name' => 'editratingpoint', 'value' => 100],
            ['name' => 'ratinglist',      'value' => 20],
        ]);

        $exists = DB::table('notices')->where('type', 'rating')->exists();

        if (! $exists) {
            $notice = $this->notices[setting('language')] ?? $this->notices['en'];

            DB::table('notices')->insert([
                'type'       => 'rating',
                'name'       => $notice['name'],
                'text'       => $notice['text'],
                'user_id'    => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'protect'    => 1,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', ['editratingpoint', 'ratinglist'])->delete();
    }
};
