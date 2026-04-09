<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * Accepts one or more comma-separated roles:
     *   ->middleware('role:hr_admin,manager')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return redirect()->route('login')
                ->withErrors(['employee_code' => 'Your account has been deactivated.']);
        }

        if (! $user->hasRole($roles)) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
