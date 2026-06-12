<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Payment\Models\PaidAdvert;
use Modules\Payment\Requests\SettingRequest;

class PaymentSettingController extends Controller
{
    /**
     * Settings
     */
    public function index(): View
    {
        $advert = new PaidAdvert();
        $places = $advert->getPlaces();
        $prices = config('payment.prices');
        $shopId = config('payment.yookassa_shop_id');
        $secretKey = config('payment.yookassa_secret_key');

        return view('payment::admin/settings', compact('places', 'prices', 'shopId', 'secretKey'));
    }

    /**
     * Save settings
     */
    public function save(SettingRequest $request): RedirectResponse
    {
        // Пустые значения не сохраняем, чтобы не копить мусор в settings
        $settings = array_filter(
            $request->validated(),
            static fn ($value) => $value !== null && $value !== ''
        );

        Module::query()
            ->where('name', 'Payment')
            ->update(['settings' => $settings]);

        clearCache('modules');

        return redirect()
            ->back()
            ->with('success', __('payment::payments.settings_success_saved'));
    }
}
