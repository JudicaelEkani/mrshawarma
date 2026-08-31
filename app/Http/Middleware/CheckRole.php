<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Utilisation dans les routes : ->middleware('role:admin') ou ->middleware('role:livreur,admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            return response()->json(['error' => 'Accès refusé pour ce rôle.'], 403);
        }

        return $next($request);
    }
}
