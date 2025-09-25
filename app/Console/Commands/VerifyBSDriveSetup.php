<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class VerifyBSDriveSetup extends Command
{
    protected $signature = 'bsdrive:verify';
    protected $description = 'Verifica se o sistema de pastas bsdrive está funcionando';

    public function handle()
    {
        $this->info('🔍 Verificando sistema BSDrive...');
        
        // Testar usuário sem pasta
        $userWithoutFolder = User::whereNull('google_drive_folder_id')->first();
        
        if ($userWithoutFolder) {
            $this->info("👤 Testando usuário sem pasta: {$userWithoutFolder->name}");
            
            try {
                $folderId = $userWithoutFolder->getOrCreatePersonalFolder();
                $this->info("✅ Pasta criada com sucesso: {$folderId}");
                $this->info("📁 Estrutura: bsdrive/Usuario_{$userWithoutFolder->id}_{$userWithoutFolder->name}");
                
                // Verificar se foi salvo
                $userWithoutFolder->refresh();
                if ($userWithoutFolder->google_drive_folder_id) {
                    $this->info("💾 Pasta salva no banco de dados");
                } else {
                    $this->error("❌ Pasta não foi salva no banco");
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Erro: " . $e->getMessage());
            }
        } else {
            $this->info("ℹ️ Todos os usuários já possuem pastas");
        }
        
        // Listar usuários com pastas
        $usersWithFolders = User::whereNotNull('google_drive_folder_id')->count();
        $this->info("📊 Usuários com pastas: {$usersWithFolders}");
        
        return 0;
    }
}
