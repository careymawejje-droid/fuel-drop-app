<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProviderManagementController extends Controller
{
    public function index(): View
    {
        $providers = Provider::query()
            ->with('designatedAdmin')
            ->withCount(['branches', 'drivers', 'trucks', 'orders'])
            ->orderBy('name')
            ->paginate(15);

        return view('admin.providers.manage-index', compact('providers'));
    }

    public function create(): View
    {
        return view('admin.providers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:providers,name'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $provider = Provider::create($validated + ['status' => 'active']);

        return to_route('admin.providers.edit', $provider)
            ->with('status', 'Provider created. Create its designated Provider account to enable operations.');
    }

    public function edit(Provider $provider): View
    {
        $provider->load('designatedAdmin');
        $providerAccountIsActive = $provider->admin_user_id !== null
            && (bool) $provider->users()
                ->whereKey($provider->admin_user_id)
                ->value('provider_user.is_active');

        return view('admin.providers.edit', compact('provider', 'providerAccountIsActive'));
    }

    public function update(Request $request, Provider $provider): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('providers', 'name')->ignore($provider->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', Rule::in(['active', 'suspended', 'inactive'])],
        ]);

        $provider->update($validated);

        return to_route('admin.providers.edit', $provider)
            ->with('status', 'Provider details updated.');
    }
}
