<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $notifications = $user->notifications()
            ->with('order')
            ->latest()
            ->paginate(15);

        return view('notifications', compact('notifications'));
    }

    public function markRead(Request $request, UserNotification $notification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $ownedNotification = $user->notifications()
            ->whereKey($notification->getKey())
            ->firstOrFail();

        if ($ownedNotification->read_at === null) {
            $ownedNotification->update(['read_at' => now()]);
        }

        return back();
    }
}
