<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PlatformOrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 64);
        $orders = Order::query()
            ->with(['customer', 'provider', 'driver.user', 'truck', 'payment'])
            ->searchReference($search)
            ->latest('placed_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'search'));
    }
}
