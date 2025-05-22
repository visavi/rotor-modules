<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use App\Models\BaseModel;
use Illuminate\Support\Facades\Date;
use Modules\Payment\Services\YooKassaService;

/**
 * Class Order
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $type
 * @property int    $amount
 * @property string $currency
 * @property string $token
 * @property string $payment_id
 * @property string $status
 * @property array  $data
 * @property Date   $created_at
 * @property Date   $updated_at
 */
class Order extends BaseModel
{
    public const TYPE_ADVERT = 'advert';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * Get the status name.
     */
    public function statusName(): string
    {
        return YooKassaService::STATUSES[$this->status] ?? 'Unknown';
    }

    /**
     * Get the status message.
     */
    public function statusMessage(): array
    {
        return match ($this->status) {
            YooKassaService::SUCCEEDED => [
                'style'   => 'text-success',
                'title'   => __('payment::payments.title_success'),
                'message' => __('payment::payments.message_success'),
            ],
            YooKassaService::PENDING,
            YooKassaService::WAITING_FOR_CAPTURE => [
                'style'   => 'text-warning',
                'title'   => __('payment::payments.title_pending'),
                'message' => __('payment::payments.message_pending'),
            ],
            default => [
                'style'   => 'text-danger',
                'title'   => __('payment::payments.title_error'),
                'message' => __('payment::payments.message_error'),
            ],
        };
    }
}
