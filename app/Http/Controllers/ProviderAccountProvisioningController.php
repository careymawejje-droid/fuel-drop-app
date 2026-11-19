<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProviderAccountProvisioningController extends Controller
{
    public function store(Request $request, Provider $provider): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        if ($provider->admin_user_id !== null) {
            throw ValidationException::withMessages([
                'provider' => 'This provider already has a designated Provider account.',
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        DB::transaction(function () use ($provider, $validated): void {
            $providerUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
            ]);

            $providerUser->assignRole('provider');
            $provider->users()->attach($providerUser->id, ['is_active' => true]);
            $provider->update(['admin_user_id' => $providerUser->id]);
        });

        return back()->with('status', 'Provider account created.');
    }

    public function updateStatus(Request $request, Provider $provider): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($provider->admin_user_id !== null, 404);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);
        $isActive = (bool) $validated['is_active'];

        abort_unless($provider->users()->whereKey($provider->admin_user_id)->exists(), 404);
        $provider->users()->updateExistingPivot($provider->admin_user_id, [
            'is_active' => $isActive,
        ]);

        return back()->with('status', $isActive ? 'Provider account activated.' : 'Provider account suspended.');
    }
}
