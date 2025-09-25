<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class EnsureUserPersonalFolder
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        
        // Verificar se o usuário tem pasta pessoal
        if (!$user->hasPersonalFolder()) {
            try {
                $user->getOrCreatePersonalFolder();
                \Log::info("Pasta pessoal criada automaticamente para usuário {$user->id} no login");
            } catch (\Exception $e) {
                \Log::warning("Não foi possível criar pasta pessoal para usuário {$user->id} no login: " . $e->getMessage());
            }
        }
    }
}
