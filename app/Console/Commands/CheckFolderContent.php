<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;

class CheckFolderContent extends Command
{
    protected $signature = 'check:folder-content {folder_id}';
    protected $description = 'Verifica o conteúdo de uma pasta específica no Google Drive';

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $folderId = $this->argument('folder_id');
        
        $this->info("🔍 Verificando conteúdo da pasta: {$folderId}");
        $this->newLine();
        
        try {
            // Verificar se a pasta existe
            if (!$this->googleDriveService->fileExists($folderId)) {
                $this->error("❌ Pasta não encontrada no Google Drive!");
                return 1;
            }
            
            // Buscar informações da pasta
            $folder = $this->googleDriveService->getFolder($folderId);
            $this->info("📁 Pasta: {$folder['name']}");
            $this->newLine();
            
            // Listar conteúdo
            $items = $this->googleDriveService->listFiles($folderId, 'files(id,name,mimeType,size,createdTime,modifiedTime)');
            
            if (empty($items)) {
                $this->warn("   Pasta vazia!");
            } else {
                $this->info("📋 Conteúdo da pasta:");
                foreach ($items as $item) {
                    $type = $item['mimeType'] === 'application/vnd.google-apps.folder' ? '📂' : '📄';
                    $size = isset($item['size']) ? ' (' . number_format($item['size']) . ' bytes)' : '';
                    $this->line("   {$type} {$item['name']}{$size} - ID: {$item['id']}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
} 