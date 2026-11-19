<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Provider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProviderBranchController extends Controller
{
    public function index(Provider $provider): View
    {
        $this->authorize('manageBranches', $provider);

        $branches = $provider->branches()
            ->withCount(['fuelInventory', 'drivers', 'trucks', 'orders'])
            ->orderBy('name')
            ->paginate(15);

        return view('provider.branches.index', compact('provider', 'branches'));
    }

    public function create(Provider $provider): View
    {
        $this->authorize('manageBranches', $provider);

        return view('provider.branches.create', compact('provider'));
    }

    public function store(Request $request, Provider $provider): RedirectResponse
    {
        $this->authorize('manageBranches', $provider);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branches', 'name')->where('provider_id', $provider->id),
            ],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'service_radius_metres' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $provider->branches()->create($validated);

        return to_route('provider.branches.index', $provider)
            ->with('status', 'Branch created.');
    }

    public function edit(Provider $provider, Branch $branch): View
    {
        $this->authorize('manageBranches', $provider);
        abort_unless($branch->provider_id === $provider->id, 404);

        return view('provider.branches.edit', compact('provider', 'branch'));
    }

    public function update(Request $request, Provider $provider, Branch $branch): RedirectResponse
    {
        $this->authorize('manageBranches', $provider);
        abort_unless($branch->provider_id === $provider->id, 404);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branches', 'name')->where('provider_id', $provider->id)->ignore($branch->id),
            ],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'service_radius_metres' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $branch->update($validated);

        return to_route('provider.branches.index', $provider)
            ->with('status', 'Branch details updated.');
    }
}
