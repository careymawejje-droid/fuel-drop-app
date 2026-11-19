<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use App\Models\PlatformSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Mail::to(PlatformSetting::current()->contact_email)->send(new ContactMessage(
            $validated['name'],
            $validated['email'],
            $validated['message'],
        ));

        return to_route('contact')->with('status', 'Your message has been sent.');
    }
}
