<?php

namespace Modules\Payment\Requests;

use App\Rules\EmailRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Payment\Services\PaymentService;

class BalanceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $paymentService = app(PaymentService::class);

        // Рубли целые, монеты получаются умножением на курс — округлять нечего
        return [
            'amount' => [
                'required',
                'integer',
                'min:' . $paymentService->getBalanceMin(),
                'max:' . $paymentService->getBalanceMax(),
            ],
            'email' => ['required', new EmailRule(), 'max:100'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'amount' => __('payment::payments.balance.amount'),
        ];
    }

    /**
     * Get the URL to redirect to on a validation error.
     */
    public function getRedirectUrl(): string
    {
        return '/payments/balance';
    }
}
