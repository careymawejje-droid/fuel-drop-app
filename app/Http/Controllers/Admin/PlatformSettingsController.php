<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlatformSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = PlatformSetting::current();

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contact_email' => ['required', 'email', 'max:255'],
            'delivery_fee' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ]);

        PlatformSetting::current()->update($validated);

        return to_route('admin.settings.edit')->with('status', 'Platform settings updated.');
    }
}
