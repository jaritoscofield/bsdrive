<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestPersonalFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:personal-folders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa o sistema de pastas pessoais dos usuários';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Teste do Sistema de Pastas Pessoais ===');
        
        $users = User::all();
        
        foreach ($users as $user) {
            $this->info("Usuário: {$user->name} ({$user->email})");
            $this->info("   Role: {$user->role}");
            
            if ($user->hasPersonalFolder()) {
                $folderId = $user->getPersonalFolderId();
                $this->info("   ✅ Pasta pessoal: {$folderId}");
                
                // Testar acesso
                $accessibleFolders = $user->getAccessibleFolderIds();
                $this->info("   📁 Total de pastas acessíveis: " . count($accessibleFolders));
                
                if (in_array($folderId, $accessibleFolders)) {
                    $this->info("   ✅ Usuário tem acesso à sua pasta pessoal");
                } else {
                    $this->error("   ❌ Usuário NÃO tem acesso à sua pasta pessoal");
                }
            } else {
                $this->error("   ❌ Sem pasta pessoal");
            }
            
            $this->info('');
        }
        
        $this->info('=== Teste de Isolamento ===');
        
        $user1 = User::find(2); // Admin Sistema
        $user2 = User::find(4); // Usuário Comum
        
        if ($user1 && $user2) {
            $this->info("Testando isolamento entre usuários:");
            $this->info("   Usuário 1: {$user1->name} - Pasta: {$user1->getPersonalFolderId()}");
            $this->info("   Usuário 2: {$user2->name} - Pasta: {$user2->getPersonalFolderId()}");
            
            $user2Folders = $user2->getAccessibleFolderIds();
            if (in_array($user1->getPersonalFolderId(), $user2Folders)) {
                $this->error("   ❌ PROBLEMA: Usuário 2 pode acessar pasta do Usuário 1!");
            } else {
                $this->info("   ✅ Isolamento OK: Usuário 2 não pode acessar pasta do Usuário 1");
            }
        }
        
        $this->info('');
        $this->info('=== Teste Concluído ===');
    }
}
