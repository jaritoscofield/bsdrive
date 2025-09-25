<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;
use App\Models\User;

class ForceRefreshFolders extends Command
{
    protected $signature = 'force:refresh-folders {userId?}';
    protected $description = 'Força uma atualização da lista de pastas';

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $userId = $this->argument('userId') ?? 4;
        $user = User::find($userId);

        if (!$user) {
            $this->error("Usuário não encontrado: {$userId}");
            return 1;
        }

        $this->info("Forçando atualização da lista de pastas para: {$user->name}");

        try {
            $personalFolderId = $user->getOrCreatePersonalFolder();
            $this->info("Pasta pessoal ID: {$personalFolderId}");

            // Forçar atualização com query específica
            $this->info("Buscando todas as pastas...");
            $allFiles = $this->googleDriveService->listFiles($personalFolderId, 'files(id,name,mimeType,parents,createdTime,modifiedTime)');
            
            $this->info("Total de itens encontrados: " . count($allFiles));
            
            $folders = [];
            foreach ($allFiles as $file) {
                if (isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder') {
                    $folders[] = $file;
                }
            }
            
            $this->info("Total de pastas: " . count($folders));
            
            foreach ($folders as $folder) {
                $createdTime = $folder['createdTime'] ?? 'N/A';
                $this->line("📁 {$folder['name']} (ID: {$folder['id']}) - Criada: {$createdTime}");
            }

            // Verificar especificamente a pasta que criamos
            $this->info("\nVerificando pasta específica 'pasta':");
            $targetFolder = null;
            foreach ($folders as $folder) {
                if ($folder['name'] === 'pasta') {
                    $targetFolder = $folder;
                    break;
                }
            }
            
            if ($targetFolder) {
                $this->info("✅ Pasta 'pasta' encontrada na lista!");
                $this->info("ID: {$targetFolder['id']}");
                $this->info("Criada: {$targetFolder['createdTime']}");
            } else {
                $this->error("❌ Pasta 'pasta' NÃO encontrada na lista!");
                
                // Tentar buscar diretamente
                $this->info("Tentando buscar diretamente...");
                try {
                    $directFolder = $this->googleDriveService->getFolder('1ehYNN2MxJqxtk5M7kC2ZKnlwgMSS8LJb');
                    if ($directFolder) {
                        $this->info("✅ Pasta encontrada diretamente: {$directFolder->getName()}");
                        $this->info("Isso indica um problema de cache na listagem.");
                    }
                } catch (\Exception $e) {
                    $this->error("Erro ao buscar pasta diretamente: " . $e->getMessage());
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Erro ao atualizar lista: " . $e->getMessage());
            return 1;
        }
    }
} 