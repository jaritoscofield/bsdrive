<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;
use App\Models\User;

class TestUploadFile extends Command
{
    protected $signature = 'test:upload-file {filePath} {userId?}';
    protected $description = 'Testa o upload de um arquivo para o Google Drive';

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $filePath = $this->argument('filePath');
        $userId = $this->argument('userId') ?? 4;
        
        $this->info("Testando upload de arquivo: {$filePath}");
        
        if (!file_exists($filePath)) {
            $this->error("❌ Arquivo não encontrado: {$filePath}");
            return 1;
        }

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

            $fileName = basename($filePath);
            $fileSize = filesize($filePath);
            
            $this->info("📄 Arquivo: {$fileName}");
            $this->info("📏 Tamanho: {$fileSize} bytes");

            // Fazer upload do arquivo
            $this->info("🚀 Iniciando upload...");
            $uploadedFile = $this->googleDriveService->uploadFile($filePath, $fileName, $personalFolderId);
            
            $this->info("✅ Arquivo enviado com sucesso!");
            $this->info("📁 Nome: {$uploadedFile->getName()}");
            $this->info("🆔 ID: {$uploadedFile->getId()}");
            $this->info("📏 Tamanho: {$uploadedFile->getSize()} bytes");
            $this->info("📅 Criado: {$uploadedFile->getCreatedTime()}");

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erro durante o teste: " . $e->getMessage());
            return 1;
        }
    }
} 