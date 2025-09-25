<?php

namespace App\Http\Middleware;

use App\Jobs\AutoSyncGoogleDriveJob;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoSyncMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Verificar se é uma criação de pasta via interface
        if ($request->is('folders') && $request->isMethod('POST')) {
            // Disparar job de sincronização em background
            AutoSyncGoogleDriveJob::dispatch()->delay(now()->addSeconds(30));
        }

        return $response;
    }
} 