<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Provider;
use App\Models\Truck;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TruckProvisioningController extends Controller
{
    public function index(Provider $provider): View
    {
        $this->authorize('view', $provider);

        $trucks = $provider->trucks()
            ->with('branch')
            ->orderBy('name')
            ->paginate(15);

        return view('provider.trucks.index', compact('provider', 'trucks'));
    }

    public function create(Provider $provider): View
    {
        $this->authorize('manageTrucks', $provider);
        $branches = $provider->branches()->orderBy('name')->get();

        return view('provider.trucks.create', compact('provider', 'branches'));
    }

    public function store(Request $request, Provider $provider): RedirectResponse
    {
        $this->authorize('manageTrucks', $provider);

        $validated = $request->validate([
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('provider_id', $provider->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'plate' => ['required', 'string', 'max:255', 'unique:trucks,plate'],
            'capacity_litres' => ['nullable', 'numeric', 'gt:0', 'decimal:0,3'],
            'status' => ['required', Rule::in(['available', 'maintenance', 'inactive'])],
        ]);

        $provider->trucks()->create($validated);

        return to_route('provider.trucks.index', $provider)
            ->with('status', 'Truck added.');
    }

    public function edit(Provider $provider, Truck $truck): View
    {
        $this->authorize('manageTrucks', $provider);
        abort_unless($truck->provider_id === $provider->id, 404);
        $branches = $provider->branches()->orderBy('name')->get();

        return view('provider.trucks.edit', compact('provider', 'truck', 'branches'));
    }

    public function update(Request $request, Provider $provider, Truck $truck): RedirectResponse
    {
        $this->authorize('manageTrucks', $provider);
        abort_unless($truck->provider_id === $provider->id, 404);

        $validated = $request->validate([
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('provider_id', $provider->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'plate' => [
                'required',
                'string',
                'max:255',
                Rule::unique('trucks', 'plate')->ignore($truck->id),
            ],
            'capacity_litres' => ['nullable', 'numeric', 'gt:0', 'decimal:0,3'],
            'status' => ['required', Rule::in(['available', 'maintenance', 'inactive', 'assigned'])],
        ]);

        DB::transaction(function () use ($request, $provider, $truck, $validated): void {
            $lockedTruck = Truck::query()
                ->whereKey($truck->id)
                ->where('provider_id', $provider->id)
                ->lockForUpdate()
                ->firstOrFail();
            $previousStatus = $lockedTruck->status;
            $hasActiveAssignment = Order::query()
                ->where('truck_id', $lockedTruck->id)
                ->whereIn('status', ['assigned', 'fuel_loading', 'out_for_delivery'])
                ->exists();
            $branchChanged = array_key_exists('branch_id', $validated)
                && $validated['branch_id'] != $lockedTruck->branch_id;

            if ($validated['status'] === 'assigned' && $lockedTruck->status !== 'assigned') {
                throw ValidationException::withMessages([
                    'status' => 'A truck can only be marked assigned by an active delivery assignment.',
                ]);
            }

            if ($hasActiveAssignment && ($validated['status'] !== 'assigned' || $branchChanged)) {
                throw ValidationException::withMessages([
                    'status' => 'Complete or cancel the active delivery before changing truck status or branch.',
                ]);
            }

            if ($hasActiveAssignment && filled($validated['capacity_litres'] ?? null)) {
                $activeOrders = Order::query()
                    ->where('truck_id', $lockedTruck->id)
                    ->whereIn('status', ['assigned', 'fuel_loading', 'out_for_delivery'])
                    ->with('fuels')
                    ->get();
                $largestOrder = $activeOrders->max(
                    fn (Order $order): float => (float) $order->fuels->sum(fn ($fuel): float => (float) $fuel->pivot->quantity_litres),
                );

                if ((float) $validated['capacity_litres'] < $largestOrder) {
                    throw ValidationException::withMessages([
                        'capacity_litres' => 'Truck capacity cannot be reduced below an active order quantity.',
                    ]);
                }
            }

            $lockedTruck->update($validated);

            if ($validated['status'] !== $previousStatus) {
                $provider->notifyActiveMembers(
                    'Truck status updated for '.$lockedTruck->plate,
                    except: $request->user(),
                );
            }
        });

        return to_route('provider.trucks.index', $provider)
            ->with('status', 'Truck details updated.');
    }
}
