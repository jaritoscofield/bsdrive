<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class TestPersistence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:persistence';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa persistência de pastas no Google Drive';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info("=== Teste de Persistência de Pastas no Google Drive ===");
            
            $service = new GoogleDriveService();
            
            // Criar pasta
            $this->info("1. Criando pasta de teste...");
            $folder = $service->createFolder('Teste Persistencia - ' . date('Y-m-d H:i:s'), null);
            $this->info("   ✓ Pasta criada com ID: " . $folder['id']);
            $this->info("   ✓ Nome: " . $folder['name']);
            
            // Aguardar 5 segundos
            $this->info("\n2. Aguardando 5 segundos...");
            sleep(5);
            
            // Verificar se a pasta ainda existe
            $this->info("\n3. Verificando se a pasta ainda existe...");
            try {
                $folderCheck = $service->getFolder($folder['id']);
                $this->info("   ✓ Pasta ainda existe!");
                $this->info("   ✓ Nome: " . $folderCheck['name']);
                $this->info("   ✓ ID: " . $folderCheck['id']);
            } catch (\Exception $e) {
                $this->error("   ✗ ERRO: Pasta não encontrada! " . $e->getMessage());
            }
            
            // Listar pastas na raiz para confirmar
            $this->info("\n4. Listando pastas na raiz...");
            $files = $service->listFiles(null, 'files(id,name,mimeType,parents)');
            $folders = array_filter($files, function($file) {
                return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
            });
            
            $this->info("   Total de pastas encontradas: " . count($folders));
            foreach ($folders as $f) {
                if (strpos($f['name'], 'Teste') !== false) {
                    $this->info("   - " . $f['name'] . " (ID: " . $f['id'] . ")");
                }
            }
            
            $this->info("\n=== Teste Concluído ===");
            
        } catch (\Exception $e) {
            $this->error("ERRO: " . $e->getMessage());
        }
    }
}
