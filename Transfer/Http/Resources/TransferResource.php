<?php

declare(strict_types=1);

namespace Modules\Transfer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Transfer\Models\Transfer;

/** @mixin Transfer */
class TransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $outgoing = $this->user_id === getUser('id');

        return [
            'id'   => $this->id,
            'type' => $outgoing ? 'out' : 'in',
            // Собеседник перевода: у отправленного — получатель, у полученного — отправитель
            'user'       => $outgoing ? $this->recipientUser->login : $this->user->login,
            'total'      => $this->total,
            'text'       => $this->text,
            'created_at' => dateFixed($this->created_at, 'c', true),
        ];
    }
}
