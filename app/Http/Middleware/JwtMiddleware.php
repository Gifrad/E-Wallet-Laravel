<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            if ($e instanceof  TokenInvalidException) {
                return response()->json(["message" => "Token Invalid"], 401);
            } else if ($e instanceof TokenExpiredException) {
                return response()->json(["message" => "Token Expired"], 401);
            } else {
                return response()->json(["message" => "Authorization code not found"], 401);
            }
        }
        return $next($request);
    }
}
