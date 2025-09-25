<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TestUserAccess extends Command
{
    protected $signature = 'test:user-access {user_id}';
    protected $description = 'Testa o acesso de um usuário às pastas';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("Usuário com ID {$userId} não encontrado.");
            return;
        }

        $this->info("Testando acesso do usuário: {$user->name} (ID: {$user->id})");
        $this->info("Empresa: " . ($user->company ? $user->company->name : 'Sem empresa'));
        $this->info("Role: {$user->role}");
        $this->info("");

        // Testar pastas diretas do usuário
        $this->info("=== PASTAS DIRETAS DO USUÁRIO ===");
        $userFolders = $user->userFolders;
        if ($userFolders->count() > 0) {
            foreach ($userFolders as $userFolder) {
                $this->line("✓ Pasta: {$userFolder->folder_name} (ID: {$userFolder->google_drive_folder_id})");
                $this->line("  Permissão: {$userFolder->permission_level}");
                $this->line("  Ativo: " . ($userFolder->active ? 'Sim' : 'Não'));
                $this->line("");
            }
        } else {
            $this->line("❌ Nenhuma pasta direta encontrada");
            $this->line("");
        }

        // Testar pastas da empresa
        if ($user->company) {
            $this->info("=== PASTAS DA EMPRESA ===");
            $companyFolders = $user->company->companyFolders;
            if ($companyFolders->count() > 0) {
                foreach ($companyFolders as $companyFolder) {
                    $this->line("✓ Pasta: {$companyFolder->folder_name} (ID: {$companyFolder->google_drive_folder_id})");
                    $this->line("  Ativo: " . ($companyFolder->active ? 'Sim' : 'Não'));
                    $this->line("");
                }
            } else {
                $this->line("❌ Nenhuma pasta da empresa encontrada");
                $this->line("");
            }
        }

        // Testar método canAccessCompanyFolder
        $this->info("=== TESTE DE ACESSO ===");
        $testFolderId = '14iPJ5CB3xDWbTMbLr1MTIBabVxvvEl5o';
        $canAccess = $user->canAccessCompanyFolder($testFolderId);
        $this->line("Pasta de teste: {$testFolderId}");
        $this->line("canAccessCompanyFolder: " . ($canAccess ? '✓ Sim' : '❌ Não'));

        // Testar método hasFolderAccess
        $hasAccess = $user->hasFolderAccess($testFolderId);
        $this->line("hasFolderAccess: " . ($hasAccess ? '✓ Sim' : '❌ Não'));

        // Testar método getAccessibleFolderIds
        $accessibleIds = $user->getAccessibleFolderIds();
        $this->line("getAccessibleFolderIds: " . count($accessibleIds) . " pastas");
        foreach ($accessibleIds as $id) {
            $this->line("  - {$id}");
        }
    }
} 