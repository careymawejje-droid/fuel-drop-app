<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Order;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DriverProvisioningController extends Controller
{
    public function index(Provider $provider): View
    {
        $this->authorize('manageDrivers', $provider);

        $drivers = $provider->drivers()
            ->with(['user', 'branch'])
            ->latest()
            ->paginate(15);

        return view('admin.providers.drivers.index', compact('provider', 'drivers'));
    }

    public function store(Request $request, Provider $provider): RedirectResponse
    {
        $this->authorize('manageDrivers', $provider);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', 'min:8'],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('provider_id', $provider->id),
            ],
            'truck_plate' => ['nullable', 'string', 'max:255', 'unique:drivers,truck_plate'],
        ]);

        DB::transaction(function () use ($provider, $validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole('driver');

            Driver::create([
                'user_id' => $user->id,
                'provider_id' => $provider->id,
                'branch_id' => $validated['branch_id'] ?? null,
                'truck_plate' => $validated['truck_plate'] ?? null,
            ]);
        });

        return back()->with('status', 'Driver created.');
    }

    public function edit(Provider $provider, Driver $driver): View
    {
        $this->authorize('manage', $driver);
        abort_unless($driver->provider_id === $provider->id, 404);

        $driver->load('user', 'branch');
        $branches = $provider->branches()->orderBy('name')->get();

        return view('admin.drivers.edit', compact('provider', 'driver', 'branches'));
    }

    public function update(Request $request, Provider $provider, Driver $driver): RedirectResponse
    {
        $this->authorize('manage', $driver);
        abort_unless($driver->provider_id === $provider->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('provider_id', $provider->id),
            ],
            'truck_plate' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('drivers', 'truck_plate')->ignore($driver->id),
            ],
            'available_status' => ['nullable', Rule::in(['available', 'inactive', 'assigned'])],
        ]);

        DB::transaction(function () use ($request, $provider, $driver, $validated): void {
            $lockedDriver = Driver::query()
                ->whereKey($driver->id)
                ->where('provider_id', $provider->id)
                ->lockForUpdate()
                ->firstOrFail();
            $previousStatus = $lockedDriver->available_status;
            $requestedStatus = $validated['available_status'] ?? $lockedDriver->available_status;
            $hasActiveAssignment = Order::query()
                ->where('driver_id', $lockedDriver->id)
                ->whereIn('status', ['assigned', 'fuel_loading', 'out_for_delivery'])
                ->exists();
            $branchChanged = array_key_exists('branch_id', $validated)
                && $validated['branch_id'] != $lockedDriver->branch_id;

            if ($requestedStatus === 'assigned' && $lockedDriver->available_status !== 'assigned') {
                throw ValidationException::withMessages([
                    'available_status' => 'A driver can only be marked assigned by an active delivery assignment.',
                ]);
            }

            if ($hasActiveAssignment && ($requestedStatus !== 'assigned' || $branchChanged)) {
                throw ValidationException::withMessages([
                    'available_status' => 'Complete or cancel the active delivery before changing driver availability or branch.',
                ]);
            }

            $lockedDriver->user->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
            ]);

            $lockedDriver->update([
                'branch_id' => $validated['branch_id'] ?? null,
                'truck_plate' => $validated['truck_plate'] ?? null,
                'available_status' => $requestedStatus,
            ]);

            if ($requestedStatus !== $previousStatus) {
                $provider->notifyActiveMembers(
                    'Driver availability updated for '.$lockedDriver->user->name,
                    except: $request->user(),
                );
            }
        });

        return to_route('provider.providers.drivers.index', $provider)
            ->with('status', 'Driver details updated.');
    }
}
