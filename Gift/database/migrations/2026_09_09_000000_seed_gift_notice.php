<?php

declare(strict_types=1);

use App\Models\Notice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Тип шаблона уведомления
     */
    private const TYPE = 'gift_send';

    /**
     * Migrate Up.
     */
    public function up(): void
    {
        if (! Schema::hasTable('notices')) {
            return;
        }

        // Миграции выполняются до включения модуля, переводы еще не зарегистрированы
        Lang::addNamespace('gift', base_path('modules/Gift/resources/lang'));

        Notice::query()->updateOrCreate(
            ['type' => self::TYPE],
            [
                'name'    => __('gift::gifts.notice_name'),
                'text'    => __('gift::gifts.notice_text'),
                'user_id' => 1,
                'protect' => 1,
            ],
        );
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        if (Schema::hasTable('notices')) {
            Notice::query()->where('type', self::TYPE)->delete();
        }
    }
};
