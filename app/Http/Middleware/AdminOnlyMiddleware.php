<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnlyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se o usuário está autenticado
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Você precisa estar logado para acessar esta página.');
        }

        // Verificar se o usuário é o administrador principal (ID = 1)
        if (auth()->id() !== 1) {
            return redirect()->route('dashboard')->with('error', 'Acesso negado. Esta funcionalidade é restrita ao administrador principal.');
        }

        return $next($request);
    }
}
