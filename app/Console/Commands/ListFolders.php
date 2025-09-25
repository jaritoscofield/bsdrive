<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;
use App\Models\User;

class ListFolders extends Command
{
    protected $signature = 'list:folders {userId?}';
    protected $description = 'Lista apenas as pastas na pasta pessoal do usuário';

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

        $this->info("Listando pastas do usuário: {$user->name}");

        try {
            $personalFolderId = $user->getOrCreatePersonalFolder();
            $files = $this->googleDriveService->listFiles($personalFolderId);
            
            $folders = [];
            foreach ($files as $file) {
                if ($file->getMimeType() === 'application/vnd.google-apps.folder') {
                    $folders[] = [
                        'name' => $file->getName(),
                        'id' => $file->getId(),
                        'created' => $file->getCreatedTime()
                    ];
                }
            }

            $this->info("Total de pastas encontradas: " . count($folders));
            
            if (empty($folders)) {
                $this->warn("Nenhuma pasta encontrada!");
            } else {
                foreach ($folders as $folder) {
                    $this->line("📁 {$folder['name']} (ID: {$folder['id']}) - Criada: {$folder['created']}");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Erro ao listar pastas: " . $e->getMessage());
            return 1;
        }
    }
} 