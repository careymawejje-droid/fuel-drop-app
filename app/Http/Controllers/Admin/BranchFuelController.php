<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BranchFuel;
use App\Models\Fuel;
use App\Models\Provider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BranchFuelController extends Controller
{
    public function index(Provider $provider): View
    {
        $this->authorize('manageInventory', $provider);

        $branchFuels = $provider->branchFuel()
            ->with(['branch', 'fuel'])
            ->orderBy('branch_id')
            ->orderBy('fuel_id')
            ->paginate(15);

        return view('admin.fuels.index', compact('provider', 'branchFuels'));
    }

    public function create(Provider $provider): View
    {
        $this->authorize('manageInventory', $provider);

        return view('admin.fuels.create', [
            'provider' => $provider,
            'branches' => $provider->branches()->where('status', 'active')->orderBy('name')->get(),
            'fuels' => Fuel::query()
                ->where('status', 'available')
                ->whereIn('name', ['Petrol', 'Diesel'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, Provider $provider): RedirectResponse
    {
        $this->authorize('manageInventory', $provider);

        $validated = $request->validate([
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')
                    ->where('provider_id', $provider->id)
                    ->where('status', 'active'),
            ],
            'fuel_id' => [
                'required',
                Rule::exists('fuels', 'id')->where('status', 'available'),
            ],
            'price_per_litre' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'available_stock_litres' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if (! Fuel::query()
            ->whereKey($validated['fuel_id'])
            ->where('status', 'available')
            ->whereIn('name', ['Petrol', 'Diesel'])
            ->exists()) {
            throw ValidationException::withMessages([
                'fuel_id' => 'Only available Petrol or Diesel inventory can be managed.',
            ]);
        }

        $request->validate([
            'fuel_id' => [
                Rule::unique('branch_fuel', 'fuel_id')
                    ->where('branch_id', $validated['branch_id']),
            ],
        ], [
            'fuel_id.unique' => 'This branch already has an inventory entry for that fuel.',
        ]);

        BranchFuel::create($validated);

        return to_route('provider.providers.fuels.index', $provider)
            ->with('status', 'Branch fuel inventory created.');
    }

    public function edit(Provider $provider, BranchFuel $branchFuel): View
    {
        abort_unless($branchFuel->branch->provider_id === $provider->id, 404);
        $this->authorize('manage', $branchFuel);

        return view('admin.fuels.edit', compact('provider', 'branchFuel'));
    }

    public function update(Request $request, Provider $provider, BranchFuel $branchFuel): RedirectResponse
    {
        abort_unless($branchFuel->branch->provider_id === $provider->id, 404);
        $this->authorize('manage', $branchFuel);

        $validated = $request->validate([
            'price_per_litre' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'available_stock_litres' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        DB::transaction(function () use ($branchFuel, $validated): void {
            $lockedBranchFuel = BranchFuel::query()
                ->whereKey($branchFuel->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $validated['available_stock_litres'] < (float) $lockedBranchFuel->reserved_stock_litres) {
                throw ValidationException::withMessages([
                    'available_stock_litres' => 'Available stock cannot be lower than stock already reserved for orders.',
                ]);
            }

            $lockedBranchFuel->update($validated);
        });

        return to_route('provider.providers.fuels.index', $provider)
            ->with('status', 'Branch fuel inventory updated.');
    }
}
