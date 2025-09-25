<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;
use App\Models\User;

class TestCreateFolder extends Command
{
    protected $signature = 'test:create-folder {folderName} {userId?}';
    protected $description = 'Testa a criação de uma pasta no Google Drive';

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $folderName = $this->argument('folderName');
        $userId = $this->argument('userId') ?? 4;
        
        $this->info("Testando criação de pasta: {$folderName}");
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("❌ Usuário não encontrado: {$userId}");
            return 1;
        }

        $this->info("Usuário: {$user->name} ({$user->email})");
        
        try {
            // Obter pasta pessoal do usuário
            $personalFolderId = $user->getOrCreatePersonalFolder();
            $this->info("Pasta pessoal ID: {$personalFolderId}");

            // Verificar se a pasta já existe
            $this->info("🔍 Verificando se a pasta já existe...");
            $existingFolder = $this->googleDriveService->findFolderByName($folderName, $personalFolderId);
            
            if ($existingFolder) {
                $this->info("✅ Pasta já existe: {$existingFolder->getName()} (ID: {$existingFolder->getId()})");
                return 0;
            }

            // Criar nova pasta
            $this->info("🚀 Criando nova pasta...");
            $newFolder = $this->googleDriveService->createFolder($folderName, $personalFolderId);
            
            $this->info("✅ Pasta criada com sucesso!");
            $this->info("📁 Nome: {$newFolder->getName()}");
            $this->info("🆔 ID: {$newFolder->getId()}");
            $this->info("📅 Criada: {$newFolder->getCreatedTime()}");

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erro durante o teste: " . $e->getMessage());
            return 1;
        }
    }
} 