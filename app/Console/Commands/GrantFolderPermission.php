<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserFolder;
use App\Services\GoogleDriveService;

class GrantFolderPermission extends Command
{
    protected $signature = 'grant:folder-permission {user_id} {folder_id} {--level=read}';
    protected $description = 'Dá permissão de pasta para um usuário específico';

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $userId = $this->argument('user_id');
        $folderId = $this->argument('folder_id');
        $level = $this->option('level');
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("Usuário com ID {$userId} não encontrado!");
            return 1;
        }
        
        // Verificar se a pasta existe no Google Drive
        if (!$this->googleDriveService->fileExists($folderId)) {
            $this->error("Pasta com ID {$folderId} não encontrada no Google Drive!");
            return 1;
        }
        
        // Buscar informações da pasta
        $folder = $this->googleDriveService->getFolder($folderId);
        
        $this->info("🔐 Concedendo permissão de pasta para o usuário:");
        $this->line("   Usuário: {$user->name} (ID: {$user->id})");
        $this->line("   Pasta: {$folder['name']} (ID: {$folderId})");
        $this->line("   Nível: {$level}");
        $this->newLine();
        
        // Verificar se já tem permissão
        $existingPermission = UserFolder::where('user_id', $userId)
            ->where('google_drive_folder_id', $folderId)
            ->first();
            
        if ($existingPermission) {
            $this->warn("⚠️ Usuário já tem permissão para esta pasta:");
            $this->line("   Nível atual: {$existingPermission->permission_level}");
            $this->line("   Status: " . ($existingPermission->active ? 'Ativa' : 'Inativa'));
            
            if ($this->confirm('Deseja atualizar a permissão?')) {
                $existingPermission->update([
                    'permission_level' => $level,
                    'active' => true
                ]);
                $this->info("✅ Permissão atualizada!");
            } else {
                $this->info("Operação cancelada.");
                return 0;
            }
        } else {
            // Criar nova permissão
            UserFolder::create([
                'user_id' => $userId,
                'google_drive_folder_id' => $folderId,
                'folder_name' => $folder['name'],
                'permission_level' => $level,
                'active' => true,
            ]);
            
            $this->info("✅ Permissão criada com sucesso!");
        }
        
        return 0;
    }
} 