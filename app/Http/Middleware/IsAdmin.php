<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->userType === 'ADM') {
            return $next($request);
        }

        abort(403, 'Acesso negado. Somente administradores podem acessar esta página.');
    }
}
