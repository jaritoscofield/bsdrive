<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;

class TestForceDelete extends Command
{
    protected $signature = 'test:force-delete {folderId}';
    protected $description = 'Testa a exclusão forçada de uma pasta';

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $folderId = $this->argument('folderId');
        $this->info("Testando exclusão forçada da pasta: {$folderId}");

        try {
            // Verificar se a pasta existe antes
            $folder = $this->googleDriveService->getFolder($folderId);
            if ($folder) {
                $this->info("✅ Pasta encontrada antes da exclusão: " . $folder->getName());
            }

            // Tentar exclusão forçada
            $this->info("🗑️ Iniciando exclusão forçada...");
            $result = $this->googleDriveService->forceDeleteFile($folderId);
            
            if ($result) {
                $this->info("✅ Exclusão forçada realizada com sucesso!");
            } else {
                $this->error("❌ Exclusão forçada falhou!");
            }

            // Verificar se a pasta ainda existe
            $this->info("🔍 Verificando se a pasta ainda existe...");
            try {
                $folderAfter = $this->googleDriveService->getFolder($folderId);
                if ($folderAfter) {
                    $this->error("❌ Pasta ainda existe após exclusão: " . $folderAfter->getName());
                } else {
                    $this->info("✅ Pasta não encontrada após exclusão (sucesso)!");
                }
            } catch (\Exception $e) {
                $this->info("✅ Pasta não encontrada após exclusão (erro esperado): " . $e->getMessage());
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erro durante o teste: " . $e->getMessage());
            return 1;
        }
    }
} 