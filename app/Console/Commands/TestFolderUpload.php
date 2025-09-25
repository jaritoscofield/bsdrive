<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;
use App\Models\User;

class TestFolderUpload extends Command
{
    protected $signature = 'test:folder-upload {zipPath} {userId?}';
    protected $description = 'Testa o upload de uma pasta ZIP';

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $zipPath = $this->argument('zipPath');
        $userId = $this->argument('userId') ?? 4;
        
        $this->info("Testando upload de pasta: {$zipPath}");
        
        if (!file_exists($zipPath)) {
            $this->error("❌ Arquivo ZIP não encontrado: {$zipPath}");
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

            // Criar diretório temporário para extrair o ZIP
            $tempDir = storage_path('app/temp/' . uniqid('test_upload_'));
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $this->info("Diretório temporário: {$tempDir}");

            // Copiar ZIP para diretório temporário
            $fileName = basename($zipPath);
            $tempZipPath = $tempDir . '/' . $fileName;
            copy($zipPath, $tempZipPath);

            $this->info("ZIP copiado para: {$tempZipPath}");

            // Extrair ZIP
            $zip = new \ZipArchive();
            if ($zip->open($tempZipPath) !== TRUE) {
                throw new \Exception('Não foi possível abrir o arquivo ZIP.');
            }

            $extractPath = $tempDir . '/extracted';
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0755, true);
            }

            $this->info("Extraindo ZIP para: {$extractPath}");
            $zip->extractTo($extractPath);
            $zip->close();

            // Verificar estrutura extraída
            $extractedItems = scandir($extractPath);
            $this->info("Itens extraídos: " . implode(', ', $extractedItems));

            // Verificar se há uma pasta raiz ou se os arquivos estão soltos
            $rootFolder = null;
            $hasDirectories = false;
            $hasFiles = false;
            
            foreach ($extractedItems as $item) {
                if ($item !== '.' && $item !== '..') {
                    $itemPath = $extractPath . '/' . $item;
                    if (is_dir($itemPath)) {
                        $hasDirectories = true;
                        if (!$rootFolder) {
                            $rootFolder = $itemPath;
                            $this->info("📁 Pasta raiz encontrada: {$item}");
                        }
                    } else {
                        $hasFiles = true;
                    }
                }
            }

            // Se não há pasta raiz, usar o diretório de extração como raiz
            if (!$rootFolder) {
                if ($hasFiles) {
                    $rootFolder = $extractPath;
                    $this->info("📁 Usando diretório de extração como raiz (arquivos soltos)");
                } else {
                    throw new \Exception('O arquivo ZIP está vazio ou não contém arquivos válidos.');
                }
            }

            $this->info("📁 Pasta raiz para upload: {$rootFolder}");

            // Fazer upload da pasta
            $this->info("🚀 Iniciando upload para Google Drive...");
            $results = $this->googleDriveService->uploadFolder($rootFolder, $personalFolderId);
            
            $this->info("✅ Upload concluído!");
            $this->info("📊 Resultados:");
            $this->info("   - Pastas criadas: {$results['folders_created']}");
            $this->info("   - Arquivos enviados: {$results['files_uploaded']}");
            
            if (!empty($results['errors'])) {
                $this->error("❌ Erros encontrados:");
                foreach ($results['errors'] as $error) {
                    $this->error("   - {$error}");
                }
            }

            // Limpar arquivos temporários
            $this->cleanupTempFiles($tempDir);

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erro durante o teste: " . $e->getMessage());
            
            // Limpar arquivos temporários em caso de erro
            if (isset($tempDir) && file_exists($tempDir)) {
                $this->cleanupTempFiles($tempDir);
            }
            
            return 1;
        }
    }

    private function cleanupTempFiles($tempDir)
    {
        if (file_exists($tempDir)) {
            $this->info("🧹 Limpando arquivos temporários...");
            $this->deleteDirectory($tempDir);
        }
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
} 