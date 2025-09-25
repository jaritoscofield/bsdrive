<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class SetupPersonalFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:personal-folders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura pastas pessoais no Google Drive para todos os usuários';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Configurando Pastas Pessoais no Google Drive ===');
        
        $users = User::whereNull('google_drive_folder_id')->get();
        
        if ($users->count() === 0) {
            $this->info('Todos os usuários já possuem pastas pessoais configuradas!');
            return;
        }
        
        $this->info("Configurando pastas para {$users->count()} usuário(s)...");
        
        foreach ($users as $user) {
            $this->info("Processando usuário: {$user->name} ({$user->email})");
            
            try {
                $folderId = $user->getOrCreatePersonalFolder();
                
                if ($folderId) {
                    $this->info("   ✅ Pasta criada com ID: {$folderId}");
                } else {
                    $this->error("   ❌ Erro ao criar pasta");
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Erro: " . $e->getMessage());
            }
            
            // Pequena pausa para não sobrecarregar a API
            sleep(1);
        }
        
        $this->info('');
        $this->info('=== Configuração Concluída ===');
        
        // Verificação final
        $usersWithFolders = User::whereNotNull('google_drive_folder_id')->count();
        $totalUsers = User::count();
        
        $this->info("Usuários com pastas pessoais: {$usersWithFolders}/{$totalUsers}");
    }
}
