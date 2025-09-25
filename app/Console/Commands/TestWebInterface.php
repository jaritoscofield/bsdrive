<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;

class TestWebInterface extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:web-interface';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa o comportamento da interface web para criação de pastas';

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Teste Comportamento Interface Web ===');
        
        // 1. Simular listagem inicial (index) - como o controller faz
        $this->info('1. Listando pastas na raiz (como no método index do controller)...');
        $allFiles = $this->googleDriveService->listFiles(null, 'files(id,name,mimeType,parents)');
        
        // Filtrar apenas pastas
        $folders = array_filter($allFiles, function($file) {
            return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
        });
        $folders = array_values($folders);
        
        $this->info('   Total de pastas encontradas: ' . count($folders));
        foreach ($folders as $folder) {
            $this->info('   - ' . $folder['name'] . ' (ID: ' . $folder['id'] . ')');
        }
        
        // 2. Simular criação de pasta (como o controller faz)
        $this->info('');
        $this->info('2. Criando nova pasta (como no método store do controller)...');
        $folderName = 'Teste Interface Web - ' . date('Y-m-d H:i:s');
        $newFolder = $this->googleDriveService->createFolder($folderName, null);
        $this->info('   ✓ Pasta criada: ' . $newFolder['name'] . ' (ID: ' . $newFolder['id'] . ')');
        
        // 3. Aguardar um momento
        $this->info('');
        $this->info('3. Aguardando 2 segundos...');
        sleep(2);
        
        // 4. Simular nova listagem após criação (voltando ao index)
        $this->info('');
        $this->info('4. Listando pastas novamente (como se voltasse ao index)...');
        $allFilesAfter = $this->googleDriveService->listFiles(null, 'files(id,name,mimeType,parents)');
        
        // Filtrar apenas pastas
        $foldersAfter = array_filter($allFilesAfter, function($file) {
            return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
        });
        $foldersAfter = array_values($foldersAfter);
        
        $this->info('   Total de pastas encontradas: ' . count($foldersAfter));
        foreach ($foldersAfter as $folder) {
            $isNew = $folder['id'] === $newFolder['id'];
            $indicator = $isNew ? ' [NOVA]' : '';
            $this->info('   - ' . $folder['name'] . ' (ID: ' . $folder['id'] . ')' . $indicator);
        }
        
        // 5. Verificar se a nova pasta está na lista
        $newFolderFound = false;
        foreach ($foldersAfter as $folder) {
            if ($folder['id'] === $newFolder['id']) {
                $newFolderFound = true;
                break;
            }
        }
        
        if ($newFolderFound) {
            $this->info('');
            $this->info('✅ SUCESSO: A nova pasta está visível na listagem!');
        } else {
            $this->error('');
            $this->error('❌ PROBLEMA: A nova pasta NÃO está visível na listagem!');
        }
        
        $this->info('');
        $this->info('=== Teste Concluído ===');
    }
}
