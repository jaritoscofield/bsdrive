<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\FileViewerController;
use App\Services\GoogleDriveService;

class TestFileViewer extends Command
{
    protected $signature = 'test:file-viewer {file_id}';
    protected $description = 'Testa a visualização de um arquivo';

    public function handle()
    {
        $fileId = $this->argument('file_id');
        
        try {
            $googleDriveService = app(GoogleDriveService::class);
            $file = $googleDriveService->getFile($fileId);
            
            if (!$file) {
                $this->error("Arquivo {$fileId} não encontrado.");
                return 1;
            }
            
            $this->info("📁 Arquivo encontrado: " . $file->getName());
            $this->info("📋 Tipo MIME: " . $file->getMimeType());
            $this->info("📏 Tamanho: " . ($file->getSize() ?? 0) . " bytes");
            
            // Testar determinação do tipo de visualização
            $controller = new FileViewerController($googleDriveService);
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('determineViewType');
            $method->setAccessible(true);
            
            $mimeType = $file->getMimeType();
            $fileName = $file->getName();
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $viewType = $method->invoke($controller, $mimeType, $fileExtension);
            
            $this->info("🎯 Tipo de visualização: " . $viewType);
            
            // URLs de acesso
            $this->info("\n🔗 URLs de acesso:");
            $this->info("👁️  Visualizar: http://127.0.0.1:8000/files/{$fileId}/view");
            $this->info("📄 Conteúdo: http://127.0.0.1:8000/files/{$fileId}/content");
            $this->info("📥 Download: http://127.0.0.1:8000/files/{$fileId}/download");
            
        } catch (\Exception $e) {
            $this->error("❌ Erro: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
