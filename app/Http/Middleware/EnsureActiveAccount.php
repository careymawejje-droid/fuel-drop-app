<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $accountStatus = $user?->newQuery()->whereKey($user->getKey())->value('status');

        if ($user !== null && $accountStatus !== 'active') {
            if ($request->hasSession()) {
                auth()->guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                throw new AuthenticationException('Your account is inactive.');
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is inactive. Contact an administrator for help.',
            ]);
        }

        $pendingLoginId = $request->hasSession() ? $request->session()->get('login.id') : null;
        if ($user === null && filled($pendingLoginId)
            && User::query()->whereKey($pendingLoginId)->value('status') !== 'active') {
            $request->session()->forget(['login.id', 'login.remember']);

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is inactive. Contact an administrator for help.',
            ]);
        }

        return $next($request);
    }
}
