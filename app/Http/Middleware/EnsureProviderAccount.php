<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProviderAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return $next($request);
        }

        abort_unless(
            $user?->hasRole('provider')
                && $user->providers()
                    ->wherePivot('is_active', true)
                    ->where('providers.status', 'active')
                    ->exists(),
            403,
        );

        return $next($request);
    }
}
