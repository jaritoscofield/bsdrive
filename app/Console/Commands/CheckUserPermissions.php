<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserFolder;

class CheckUserPermissions extends Command
{
    protected $signature = 'check:user-permissions {user_id?} {--folder-id=}';
    protected $description = 'Verifica as permissões de um usuário específico';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $folderId = $this->option('folder-id');
        
        if (!$userId) {
            $userId = $this->ask('Digite o ID do usuário:');
        }
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("Usuário com ID {$userId} não encontrado!");
            return 1;
        }
        
        $this->info("🔍 Verificando permissões do usuário: {$user->name} (ID: {$user->id})");
        $this->newLine();
        
        // Informações básicas do usuário
        $this->info("📋 Informações do usuário:");
        $this->line("   Nome: {$user->name}");
        $this->line("   Email: {$user->email}");
        $this->line("   Role: {$user->role}");
        $this->line("   Empresa: " . ($user->company ? $user->company->name : 'Nenhuma'));
        $this->line("   Pasta pessoal ID: " . ($user->getPersonalFolderId() ?: 'Nenhuma'));
        $this->newLine();
        
        // Permissões de pasta
        $this->info("📁 Permissões de pasta:");
        $userFolders = $user->userFolders()->with('user')->get();
        
        if ($userFolders->isEmpty()) {
            $this->warn("   Nenhuma permissão de pasta encontrada!");
        } else {
            foreach ($userFolders as $userFolder) {
                $status = $userFolder->active ? '✅ Ativa' : '❌ Inativa';
                $this->line("   📂 {$userFolder->folder_name} (ID: {$userFolder->google_drive_folder_id}) - {$userFolder->permission_level} - {$status}");
            }
        }
        $this->newLine();
        
        // Pastas acessíveis
        $this->info("🔓 Pastas acessíveis:");
        $accessibleFolders = $user->getAccessibleFolderIds();
        
        if (empty($accessibleFolders)) {
            $this->warn("   Nenhuma pasta acessível encontrada!");
        } else {
            foreach ($accessibleFolders as $folderId) {
                $this->line("   📂 ID: {$folderId}");
            }
        }
        $this->newLine();
        
        // Verificar pasta específica se fornecida
        if ($folderId) {
            $this->info("🎯 Verificando acesso à pasta específica: {$folderId}");
            
            $hasAccess = $user->hasFolderAccess($folderId);
            $canAccessCompany = $user->canAccessCompanyFolder($folderId);
            $isPersonal = $user->getPersonalFolderId() === $folderId;
            
            $this->line("   hasFolderAccess: " . ($hasAccess ? '✅ Sim' : '❌ Não'));
            $this->line("   canAccessCompanyFolder: " . ($canAccessCompany ? '✅ Sim' : '❌ Não'));
            $this->line("   É pasta pessoal: " . ($isPersonal ? '✅ Sim' : '❌ Não'));
            
            if ($hasAccess) {
                $permissionLevel = $user->getFolderPermissionLevel($folderId);
                $this->line("   Nível de permissão: {$permissionLevel}");
            }
            
            $this->newLine();
        }
        
        // Verificar se é admin_sistema
        if ($user->role === 'admin_sistema') {
            $this->info("👑 Usuário é admin_sistema - tem acesso a todas as pastas!");
        }
        
        return 0;
    }
} 