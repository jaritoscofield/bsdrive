<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;
use App\Models\User;

class CheckPersonalFolder extends Command
{
    protected $signature = 'check:personal-folder {userId?}';
    protected $description = 'Verifica o conteúdo da pasta pessoal do usuário';

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $userId = $this->argument('userId') ?? 4; // Usuário padrão
        $user = User::find($userId);

        if (!$user) {
            $this->error("Usuário não encontrado: {$userId}");
            return 1;
        }

        $this->info("Verificando pasta pessoal do usuário: {$user->name} (ID: {$user->id})");

        try {
            $personalFolderId = $user->getOrCreatePersonalFolder();
            $this->info("ID da pasta pessoal: {$personalFolderId}");

            $files = $this->googleDriveService->listFiles($personalFolderId);
            
            $this->info("\nConteúdo da pasta pessoal:");
            $this->info("Total de itens: " . count($files));
            
            if (empty($files)) {
                $this->warn("Pasta vazia!");
            } else {
                foreach ($files as $file) {
                    $type = $file->getMimeType() === 'application/vnd.google-apps.folder' ? '📁' : '📄';
                    $size = $file->getSize() ? " ({$file->getSize()} bytes)" : '';
                    $this->line("  {$type} {$file->getName()}{$size}");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Erro ao verificar pasta pessoal: " . $e->getMessage());
            return 1;
        }
    }
} 