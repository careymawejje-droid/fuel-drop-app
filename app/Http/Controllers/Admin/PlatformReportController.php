<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\View\View;

class PlatformReportController extends Controller
{
    public function index(): View
    {
        $providers = Provider::query()
            ->withCount(['branches', 'drivers', 'trucks', 'orders'])
            ->orderBy('name')
            ->get();
        $orderStatuses = Order::query()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->orderBy('status')
            ->get();
        $paymentStatuses = Payment::query()
            ->selectRaw('status, COUNT(*) AS total, SUM(amount) AS amount')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        return view('admin.reports.index', [
            'providers' => $providers,
            'orderStatuses' => $orderStatuses,
            'paymentStatuses' => $paymentStatuses,
            'customersCount' => User::query()->whereHas('roles', fn ($query) => $query->where('name', 'customer'))->count(),
            'driversCount' => User::query()->whereHas('roles', fn ($query) => $query->where('name', 'driver'))->count(),
            'paidTotal' => Payment::query()->where('status', 'paid')->sum('amount'),
        ]);
    }
}
