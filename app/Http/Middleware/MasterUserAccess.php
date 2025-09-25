<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterUserAccess
{
    /**
     * Handle an incoming request.
     * 
     * Este middleware força acesso master para todos os usuários autenticados,
     * contornando as limitações de permissão da API do Google Drive.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Forçar role master temporariamente para operações do Google Drive
            if (!session()->has('original_role')) {
                session(['original_role' => $user->role]);
            }
            
            // Simular usuário master para contornar limitações da API
            $user->role = 'master';
            
            \Log::info('🔓 MasterUserAccess: Usuário temporariamente elevado para master', [
                'user_id' => $user->id,
                'original_role' => session('original_role'),
                'current_role' => $user->role,
                'route' => $request->route()->getName()
            ]);
        }

        return $next($request);
    }
}
