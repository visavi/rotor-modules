<?php

declare(strict_types=1);

namespace Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\PaidAdvert;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
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

        return view('payment::admin/settings', compact('places', 'prices'));
    }

    /**
     * Save settings
     */
    public function save(SettingRequest $request): RedirectResponse
    {
        Module::query()
            ->where('name', 'Payment')
            ->update(['settings' => $request->validated()]);

        clearCache('modules');

        return redirect()
            ->back()
            ->with('success', __('payment::payments.settings_success_saved'));
    }
}
