<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestUserFolder extends Command
{
    protected $signature = 'test:user-folder {user_id}';
    protected $description = 'Testa a criação de pasta pessoal para um usuário';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Usuário com ID {$userId} não encontrado.");
            return 1;
        }
        
        $this->info("🧪 Testando criação de pasta para: {$user->name}");
        $this->info("📁 Pasta atual: " . ($user->google_drive_folder_id ?? 'Nenhuma'));
        
        try {
            $folderId = $user->getOrCreatePersonalFolder();
            $this->info("✅ Pasta criada/obtida com sucesso!");
            $this->info("🆔 ID da pasta: {$folderId}");
            
            // Verificar se foi salvo no banco
            $user->refresh();
            $this->info("💾 Salvo no banco: " . ($user->google_drive_folder_id ?? 'Não salvo'));
            
        } catch (\Exception $e) {
            $this->error("❌ Erro: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
