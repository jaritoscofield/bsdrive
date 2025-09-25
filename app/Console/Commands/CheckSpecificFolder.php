<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;

class CheckSpecificFolder extends Command
{
    protected $signature = 'check:specific-folder {folderId}';
    protected $description = 'Verifica uma pasta específica no Google Drive';

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $folderId = $this->argument('folderId');

        $this->info("Verificando pasta: {$folderId}");

        try {
            $folder = $this->googleDriveService->getFolder($folderId);
            
            if ($folder) {
                $this->info("✅ Pasta encontrada!");
                $this->info("Nome: " . $folder->getName());
                $this->info("ID: " . $folder->getId());
                $this->info("Tipo: " . $folder->getMimeType());
                $this->info("Criada: " . $folder->getCreatedTime());
                $this->info("Modificada: " . $folder->getModifiedTime());
                
                // Verificar pais
                $parents = $folder->getParents();
                if ($parents) {
                    $this->info("Pasta pai: " . implode(', ', $parents));
                }
            } else {
                $this->error("❌ Pasta não encontrada!");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Erro ao verificar pasta: " . $e->getMessage());
            return 1;
        }
    }
} 