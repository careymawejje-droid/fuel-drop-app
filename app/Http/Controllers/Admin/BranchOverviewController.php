<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Contracts\View\View;

class BranchOverviewController extends Controller
{
    public function index(): View
    {
        $branches = Branch::query()
            ->with('provider')
            ->withCount(['fuelInventory', 'drivers', 'trucks', 'orders'])
            ->orderBy('provider_id')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.branches.index', compact('branches'));
    }
}
