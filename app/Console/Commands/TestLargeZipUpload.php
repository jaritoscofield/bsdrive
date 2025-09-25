<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;
use ZipArchive;

class TestLargeZipUpload extends Command
{
    protected $signature = 'test:large-zip-upload {zipPath} {--user-id=1}';
    protected $description = 'Testa o upload de um arquivo ZIP grande';

    public function handle()
    {
        $zipPath = $this->argument('zipPath');
        $userId = $this->option('user-id');

        if (!file_exists($zipPath)) {
            $this->error("Arquivo ZIP não encontrado: {$zipPath}");
            return 1;
        }

        $this->info("🧪 TESTE DE UPLOAD DE ZIP GRANDE");
        $this->info("Arquivo: {$zipPath}");
        $this->info("Usuário ID: {$userId}");

        // Verificar tamanho do arquivo
        $fileSize = filesize($zipPath);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);
        $this->info("Tamanho: {$fileSize} bytes ({$fileSizeMB}MB)");

        // Aumentar limites
        ini_set('max_execution_time', 1800);
        ini_set('memory_limit', '1024M');
        
        $this->info("Limites configurados:");
        $this->info("- max_execution_time: " . ini_get('max_execution_time'));
        $this->info("- memory_limit: " . ini_get('memory_limit'));

        try {
            // 1. TESTE DE EXTRAÇÃO
            $this->info("\n📦 TESTE 1: EXTRAÇÃO DO ZIP");
            $extractionResult = $this->testZipExtraction($zipPath);
            
            if (!$extractionResult['success']) {
                $this->error("❌ Falha na extração: " . $extractionResult['error']);
                return 1;
            }
            
            $this->info("✅ Extração bem-sucedida");
            $this->info("Arquivos extraídos: " . $extractionResult['files_count']);
            $this->info("Pastas extraídas: " . $extractionResult['folders_count']);

            // 2. TESTE DE UPLOAD
            $this->info("\n🚀 TESTE 2: UPLOAD PARA GOOGLE DRIVE");
            $uploadResult = $this->testGoogleDriveUpload($extractionResult['extract_path'], $userId);
            
            if (!$uploadResult['success']) {
                $this->error("❌ Falha no upload: " . $uploadResult['error']);
                return 1;
            }
            
            $this->info("✅ Upload bem-sucedido");
            $this->info("Pastas criadas: " . $uploadResult['folders_created']);
            $this->info("Arquivos enviados: " . $uploadResult['files_uploaded']);

            // Limpeza
            $this->info("\n🧹 Limpando arquivos temporários...");
            $this->removeDirectory($extractionResult['temp_dir']);
            
            $this->info("🎉 TESTE CONCLUÍDO COM SUCESSO!");
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erro durante o teste: " . $e->getMessage());
            $this->error("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            
            // Limpeza em caso de erro
            if (isset($extractionResult['temp_dir']) && file_exists($extractionResult['temp_dir'])) {
                $this->removeDirectory($extractionResult['temp_dir']);
            }
            
            return 1;
        }
    }

    private function testZipExtraction($zipPath)
    {
        $this->info("Iniciando extração...");
        
        // Criar diretório temporário
        $tempDir = storage_path('app/temp/' . uniqid('large_zip_test_'));
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $extractPath = $tempDir . '/extracted';
        if (!file_exists($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        try {
            // Abrir ZIP
            $zip = new ZipArchive();
            $result = $zip->open($zipPath);
            
            if ($result !== TRUE) {
                return [
                    'success' => false,
                    'error' => "Erro ao abrir ZIP. Código: {$result}",
                    'temp_dir' => $tempDir
                ];
            }

            $this->info("ZIP aberto. Arquivos: {$zip->numFiles}");

            // Extrair
            $startTime = microtime(true);
            $extractResult = $zip->extractTo($extractPath);
            $endTime = microtime(true);
            
            if (!$extractResult) {
                return [
                    'success' => false,
                    'error' => "Falha na extração. Status: " . $zip->status,
                    'temp_dir' => $tempDir
                ];
            }
            
            $extractionTime = round($endTime - $startTime, 2);
            $this->info("Extração concluída em {$extractionTime}s");
            
            $zip->close();

            // Contar arquivos e pastas
            $stats = $this->countFilesAndFolders($extractPath);
            
            return [
                'success' => true,
                'extract_path' => $extractPath,
                'temp_dir' => $tempDir,
                'files_count' => $stats['files'],
                'folders_count' => $stats['folders'],
                'extraction_time' => $extractionTime
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'temp_dir' => $tempDir
            ];
        }
    }

    private function testGoogleDriveUpload($folderPath, $userId)
    {
        $this->info("Iniciando upload para Google Drive...");
        
        try {
            // Simular usuário
            $user = \App\Models\User::find($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'error' => "Usuário não encontrado: {$userId}"
                ];
            }

            // Obter pasta pessoal
            $personalFolderId = $user->getOrCreatePersonalFolder();
            $this->info("Pasta pessoal: {$personalFolderId}");

            // Upload usando GoogleDriveService
            $googleDriveService = app(GoogleDriveService::class);
            
            $startTime = microtime(true);
            $results = $googleDriveService->uploadFolder($folderPath, $personalFolderId);
            $endTime = microtime(true);
            
            $uploadTime = round($endTime - $startTime, 2);
            $this->info("Upload concluído em {$uploadTime}s");

            if (!empty($results['errors'])) {
                return [
                    'success' => false,
                    'error' => "Erros no upload: " . implode(', ', $results['errors'])
                ];
            }

            return [
                'success' => true,
                'folders_created' => $results['folders_created'],
                'files_uploaded' => $results['files_uploaded'],
                'upload_time' => $uploadTime,
                'root_folder_id' => $results['root_folder_id'] ?? null
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function countFilesAndFolders($path)
    {
        $files = 0;
        $folders = 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $folders++;
            } else {
                $files++;
            }
        }
        
        return ['files' => $files, 'folders' => $folders];
    }

    private function removeDirectory($dir)
    {
        if (!file_exists($dir)) return;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        rmdir($dir);
    }
} 