<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Manejar solicitudes preflight (OPTIONS)
        if ($request->getMethod() == "OPTIONS") {
            // Build a Symfony response to avoid relying on framework-specific helpers
            $symfonyResponse = new \Symfony\Component\HttpFoundation\Response('', 200);
            $symfonyResponse->headers->set('Access-Control-Allow-Origin', '*');
            $symfonyResponse->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $symfonyResponse->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
            $symfonyResponse->headers->set('Access-Control-Max-Age', '86400');

            return $symfonyResponse;
        }

        $response = $next($request);

        // Agregar headers CORS a la respuesta. Support both Illuminate and Symfony responses.
        if (is_object($response)) {
            if (method_exists($response, 'header')) {
                // Illuminate response
                $response->header('Access-Control-Allow-Origin', '*');
                $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
                $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
                $response->header('Access-Control-Expose-Headers', 'Authorization');
            } elseif (property_exists($response, 'headers') && is_object($response->headers)) {
                // Symfony response
                $response->headers->set('Access-Control-Allow-Origin', '*');
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
                $response->headers->set('Access-Control-Expose-Headers', 'Authorization');
            }
        }

        return $response;
    }
}


