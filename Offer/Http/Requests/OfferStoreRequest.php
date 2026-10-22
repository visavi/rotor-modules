<?php

declare(strict_types=1);

namespace Modules\Offer\Http\Requests;

use App\Http\Requests\CommentRequest;
use App\Services\FileService;
use Illuminate\Validation\Validator;
use Modules\Offer\Models\Offer;

/**
 * Новая запись — общая для сайта и API
 */
class OfferStoreRequest extends OfferRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['text'][] = CommentRequest::floodRule();

        return $rules + FileService::rules(Offer::$morphName);
    }

    public function after(): array
    {
        return [
            // Писать в раздел можно, только набрав порог баллов
            static function (Validator $validator): void {
                if (getUser('point') < setting('addofferspoint')) {
                    $validator->errors()->add('point', __('offer::offers.condition_add', [
                        'point' => plural(setting('addofferspoint'), setting('scorename')),
                    ]));
                }
            },
        ];
    }
}
