<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BasicAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getUser() && $request->getPassword()) {
            $credentials = [
                'email' => $request->getUser(),
                'password' => $request->getPassword(),
            ];

            if (Auth::attempt($credentials)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Unauthorized'], 401)->header('WWW-Authenticate', 'Basic realm="Access to the API"');

    }
}
