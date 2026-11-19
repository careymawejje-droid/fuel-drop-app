<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Contracts\View\View;

class PlatformPaymentController extends Controller
{
    public function index(): View
    {
        $payments = Payment::query()
            ->with(['order.customer', 'order.provider', 'collectedByDriver.user'])
            ->latest()
            ->paginate(20);

        return view('admin.payments.index', compact('payments'));
    }
}
