<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-API-Token');

        if (!$token || $token !== config('services.api.token')) {
            return response()->json([
                'message' => 'Não autorizado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
} 