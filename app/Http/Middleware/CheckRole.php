<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Si el usuario no ha iniciado sesión o su rol no coincide con los requeridos, bloquea el acceso
        if (!$request->user() || !in_array($request->user()->rol, $roles)) {
            abort(403, 'Acceso no autorizado al panel administrativo.');
        }

        return $next($request);
    }
}