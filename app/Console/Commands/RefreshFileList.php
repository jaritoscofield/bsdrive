<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;
use App\Models\User;

class RefreshFileList extends Command
{
    protected $signature = 'refresh:file-list {userId?}';
    protected $description = 'Força a atualização da lista de arquivos do usuário';

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $userId = $this->argument('userId') ?? 4;
        $user = User::find($userId);

        if (!$user) {
            $this->error("Usuário não encontrado: {$userId}");
            return 1;
        }

        $this->info("Atualizando lista de arquivos para: {$user->name}");

        try {
            $personalFolderId = $user->getOrCreatePersonalFolder();
            $this->info("Pasta pessoal ID: {$personalFolderId}");

            // Forçar atualização da lista
            $files = $this->googleDriveService->listFiles($personalFolderId, 'files(id,name,mimeType,size,createdTime,modifiedTime,parents)');
            
            $this->info("Total de itens encontrados: " . count($files));
            
            $folders = array_filter($files, function($item) {
                return isset($item['mimeType']) && $item['mimeType'] === 'application/vnd.google-apps.folder';
            });
            
            $this->info("Total de pastas: " . count($folders));
            
            foreach ($folders as $folder) {
                $this->line("📁 {$folder['name']} (ID: {$folder['id']}) - Criada: {$folder['createdTime']}");
            }

            $this->info("Lista atualizada com sucesso!");
            return 0;

        } catch (\Exception $e) {
            $this->error("Erro ao atualizar lista: " . $e->getMessage());
            return 1;
        }
    }
} 