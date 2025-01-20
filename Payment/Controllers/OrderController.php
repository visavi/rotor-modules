<?php

declare(strict_types=1);

namespace Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Payment\Models\Order;

class OrderController extends Controller
{
    /**
     * Main page
     */
    public function index(): View
    {
        $orders = Order::query()
            ->orderByDesc('created_at')
            ->paginate(10);

        Order::query()->create([
            'type' => 'advert',
            'user_id' => rand(1,19999),
            'amount' => rand(200, 1999),
            'currency' => 'RUB',
        ]);


        return view('Payment::admin/orders', compact('orders'));
    }
}
