<?php

declare(strict_types=1);

namespace Modules\Backup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Новый бэкап базы: таблицы и сжатие
 */
class BackupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sheets'   => ['required', 'array'],
            'sheets.*' => ['string'],
            'method'   => ['required', Rule::in(self::methods())],
            'level'    => ['required', 'integer', 'between:0,9'],
        ];
    }

    public function messages(): array
    {
        return [
            'sheets' => __('backup::backup.no_tables_save'),
            'method' => __('backup::backup.wrong_compression_method'),
            'level'  => __('backup::backup.wrong_compression_ratio'),
        ];
    }

    /**
     * Способы сжатия, доступные в этом PHP
     *
     * @return list<string>
     */
    public static function methods(): array
    {
        return array_values(array_filter([
            'none',
            function_exists('gzopen') ? 'gzip' : null,
            function_exists('bzopen') ? 'bzip' : null,
        ]));
    }
}
