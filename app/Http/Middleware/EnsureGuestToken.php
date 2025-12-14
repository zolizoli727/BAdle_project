<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('guest_token');

        if (!$token) {
            $token = (string) Str::uuid();
            $minutes = 60 * 24 * 365;
            Cookie::queue(cookie('guest_token', $token, $minutes));
        }

        $request->attributes->set('guest_token', $token);

        return $next($request);
    }
}
