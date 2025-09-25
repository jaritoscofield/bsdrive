<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

class TestZipExtraction extends Command
{
    protected $signature = 'test:zip-extraction {zipPath}';
    protected $description = 'Testa a extração de um arquivo ZIP';

    public function handle()
    {
        $zipPath = $this->argument('zipPath');

        if (!file_exists($zipPath)) {
            $this->error("Arquivo ZIP não encontrado: {$zipPath}");
            return 1;
        }

        $this->info("Testando extração do ZIP: {$zipPath}");

        // Verificar tamanho do arquivo
        $fileSize = filesize($zipPath);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);
        $this->info("Tamanho do arquivo: {$fileSize} bytes ({$fileSizeMB}MB)");

        // Aumentar limites para arquivos grandes
        ini_set('max_execution_time', 1800); // 30 minutos
        ini_set('memory_limit', '1024M'); // 1GB
        
        $this->info("Limites configurados:");
        $this->info("- max_execution_time: " . ini_get('max_execution_time'));
        $this->info("- memory_limit: " . ini_get('memory_limit'));

        // Criar diretório temporário
        $tempDir = storage_path('app/temp/' . uniqid('zip_test_'));
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $this->info("Diretório temporário: {$tempDir}");

        try {
            // Abrir ZIP
            $zip = new ZipArchive();
            $result = $zip->open($zipPath);
            
            if ($result !== TRUE) {
                $errorMessages = [
                    ZipArchive::ER_EXISTS => 'Arquivo já existe',
                    ZipArchive::ER_INCONS => 'ZIP inconsistente',
                    ZipArchive::ER_INVAL => 'Argumento inválido',
                    ZipArchive::ER_MEMORY => 'Erro de memória',
                    ZipArchive::ER_NOENT => 'Arquivo não encontrado',
                    ZipArchive::ER_NOZIP => 'Não é um arquivo ZIP',
                    ZipArchive::ER_OPEN => 'Erro ao abrir arquivo',
                    ZipArchive::ER_READ => 'Erro de leitura',
                    ZipArchive::ER_SEEK => 'Erro de busca'
                ];
                
                $errorMsg = isset($errorMessages[$result]) ? $errorMessages[$result] : 'Erro desconhecido';
                $this->error("Erro ao abrir ZIP. Código: {$result} - {$errorMsg}");
                return 1;
            }

            $this->info("ZIP aberto com sucesso");
            $this->info("Número de arquivos no ZIP: {$zip->numFiles}");

            // Listar conteúdo do ZIP
            $this->info("\nConteúdo do ZIP:");
            $totalSize = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];
                $size = $stat['size'];
                $totalSize += $size;
                $isDir = $stat['size'] == 0 && substr($name, -1) == '/';
                
                $icon = $isDir ? '📁' : '📄';
                $sizeStr = $isDir ? 'DIR' : number_format($size) . ' bytes';
                $this->line("  {$icon} {$name} ({$sizeStr})");
            }

            $totalSizeMB = round($totalSize / 1024 / 1024, 2);
            $this->info("\nTamanho total dos arquivos: {$totalSize} bytes ({$totalSizeMB}MB)");

            // Extrair ZIP
            $extractPath = $tempDir . '/extracted';
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0755, true);
            }

            $this->info("\nIniciando extração para: {$extractPath}");
            
            $startTime = microtime(true);
            $extractResult = $zip->extractTo($extractPath);
            $endTime = microtime(true);
            
            if (!$extractResult) {
                $this->error("Falha na extração do ZIP. Status: " . $zip->status);
                return 1;
            }
            
            $extractionTime = round($endTime - $startTime, 2);
            $this->info("Extração concluída em {$extractionTime} segundos");
            
            $zip->close();

            // Verificar arquivos extraídos
            $this->info("\nVerificando arquivos extraídos:");
            $this->listDirectoryContents($extractPath, 0);

            // Limpeza
            $this->info("\nLimpando arquivos temporários...");
            $this->removeDirectory($tempDir);
            
            $this->info("✅ Teste de extração concluído com sucesso!");
            return 0;

        } catch (\Exception $e) {
            $this->error("Erro durante o teste: " . $e->getMessage());
            $this->error("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            
            // Limpeza em caso de erro
            if (isset($tempDir) && file_exists($tempDir)) {
                $this->removeDirectory($tempDir);
            }
            
            return 1;
        }
    }

    private function listDirectoryContents($path, $level = 0)
    {
        $indent = str_repeat('  ', $level);
        $items = scandir($path);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemPath = $path . '/' . $item;
            if (is_dir($itemPath)) {
                $this->line("{$indent}📁 {$item}/");
                $this->listDirectoryContents($itemPath, $level + 1);
            } else {
                $size = filesize($itemPath);
                $sizeStr = number_format($size) . ' bytes';
                $this->line("{$indent}📄 {$item} ({$sizeStr})");
            }
        }
    }

    private function removeDirectory($dir)
    {
        if (!file_exists($dir)) return;
        
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemPath = $dir . '/' . $item;
            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
            } else {
                unlink($itemPath);
            }
        }
        
        rmdir($dir);
    }
} 