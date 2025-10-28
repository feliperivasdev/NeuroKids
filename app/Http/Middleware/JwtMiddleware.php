<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;

class JwtMiddleware extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            // Verificar si hay token
            if (!$token = JWTAuth::getToken()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Por favor inicie sesión'
                ], 401);
            }

            try {
                // Intentar autenticar al usuario
                $user = JWTAuth::parseToken()->authenticate();
            } catch (TokenBlacklistedException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'La sesión ha expirado, por favor inicie sesión nuevamente'
                ], 401);
            }
            
        } catch (Exception $e) {
            if ($e instanceof TokenBlacklistedException) {
                return response()->json([
                    'success' => false,
                    'message' => 'La sesión ha expirado, por favor inicie sesión nuevamente'
                ], 401);
            } else if ($e instanceof TokenInvalidException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido'
                ], 401);
            } else if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expirado'
                ], 401);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de autorización no encontrado'
                ], 401);
            }
        }
        
        return $next($request);
    }
}