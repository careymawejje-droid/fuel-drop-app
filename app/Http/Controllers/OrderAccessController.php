<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderAccessController extends Controller
{
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->status,
        ]);
    }
}
