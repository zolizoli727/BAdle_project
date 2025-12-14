<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || (int)($user->user_level ?? 0) !== 2) {
            abort(403);
        }

        return $next($request);
    }
}
