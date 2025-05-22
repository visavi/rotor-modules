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

        return view('payment::admin/orders', compact('orders'));
    }
}
