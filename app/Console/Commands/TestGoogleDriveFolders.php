<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class TestGoogleDriveFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-drive:test-folders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa a criação de pastas no Google Drive';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testando criação de pastas no Google Drive...');

        try {
            $googleDriveService = new GoogleDriveService();

            // Teste 1: Criar pasta raiz
            $this->info('Criando pasta raiz "BSDrive Test"...');
            $rootFolder = $googleDriveService->createFolder('BSDrive Test');
            $this->info("✓ Pasta raiz criada com ID: {$rootFolder->getId()}");

            // Teste 2: Criar subpasta
            $this->info('Criando subpasta "Documentos"...');
            $subFolder = $googleDriveService->createFolder('Documentos', $rootFolder->getId());
            $this->info("✓ Subpasta criada com ID: {$subFolder->getId()}");

            // Teste 3: Listar arquivos da pasta raiz
            $this->info('Listando conteúdo da pasta raiz...');
            $files = $googleDriveService->listFiles($rootFolder->getId());
            $this->info("✓ Encontrados " . count($files) . " itens na pasta raiz");

            foreach ($files as $file) {
                $type = $googleDriveService->isFolder($file->getMimeType()) ? '📁' : '📄';
                $this->line("  {$type} {$file->getName()} (ID: {$file->getId()})");
            }

            $this->info('✅ Todos os testes passaram! A criação real de pastas está funcionando.');

        } catch (\Exception $e) {
            $this->error('❌ Erro no teste: ' . $e->getMessage());
            $this->error('Certifique-se de que você está autenticado com OAuth2.');
            $this->info('Acesse: http://localhost/google-drive/auth');
        }
    }
}
