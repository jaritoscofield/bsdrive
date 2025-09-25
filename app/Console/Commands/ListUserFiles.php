<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;

class ListUserFiles extends Command
{
    protected $signature = 'list:user-files {user_id}';
    protected $description = 'Lista arquivos de um usuário';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = \App\Models\User::find($userId);
        
        if (!$user) {
            $this->error("Usuário {$userId} não encontrado.");
            return 1;
        }
        
        $this->info("📁 Listando arquivos do usuário: " . $user->name);
        
        if (!$user->google_drive_folder_id) {
            $this->error("Usuário não tem pasta no Google Drive.");
            return 1;
        }
        
        try {
            $googleDriveService = app(GoogleDriveService::class);
            $files = $googleDriveService->listFiles($user->google_drive_folder_id);
            
            $this->info("📊 Total de arquivos: " . count($files));
            $this->info("");
            
            foreach (array_slice($files, 0, 10) as $file) {
                $type = isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder' ? '📁' : '📄';
                $this->line("{$type} {$file['id']} - {$file['name']} ({$file['mimeType']})");
            }
            
            if (count($files) > 10) {
                $this->info("\n... e mais " . (count($files) - 10) . " arquivos");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
