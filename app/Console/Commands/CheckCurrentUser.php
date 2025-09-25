<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckCurrentUser extends Command
{
    protected $signature = 'check:current-user {userId?}';
    protected $description = 'Verifica informações do usuário atual';

    public function handle()
    {
        $userId = $this->argument('userId') ?? 4;
        $user = User::find($userId);

        if (!$user) {
            $this->error("Usuário não encontrado: {$userId}");
            return 1;
        }

        $this->info("=== Informações do Usuário ===");
        $this->info("ID: {$user->id}");
        $this->info("Nome: {$user->name}");
        $this->info("Email: {$user->email}");
        $this->info("Role: {$user->role}");
        $this->info("Company ID: {$user->company_id}");
        $this->info("Google Drive Folder ID: {$user->google_drive_folder_id}");

        $this->info("\n=== Pastas Acessíveis ===");
        $accessibleFolderIds = $user->getAccessibleFolderIds();
        $this->info("Total de pastas acessíveis: " . count($accessibleFolderIds));
        
        foreach ($accessibleFolderIds as $folderId) {
            $this->line("  - {$folderId}");
        }

        $this->info("\n=== Pasta Pessoal ===");
        $personalFolderId = $user->getOrCreatePersonalFolder();
        $this->info("ID da pasta pessoal: {$personalFolderId}");
        $this->info("Tem pasta pessoal: " . ($user->hasPersonalFolder() ? 'Sim' : 'Não'));

        return 0;
    }
} 