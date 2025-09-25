<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class DebugUserFolder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:user-folder {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug da pasta pessoal de um usuário específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Usuário com email '{$email}' não encontrado!");
            return;
        }
        
        $this->info('=== Debug da Pasta Pessoal ===');
        $this->info("Usuário: {$user->name} ({$user->email})");
        $this->info("Role: {$user->role}");
        $this->info("ID: {$user->id}");
        
        $this->info('');
        $this->info('=== Campo google_drive_folder_id ===');
        $this->info("Valor no banco: " . ($user->google_drive_folder_id ?? 'NULL'));
        
        $this->info('');
        $this->info('=== Métodos do modelo ===');
        $this->info("hasPersonalFolder(): " . ($user->hasPersonalFolder() ? 'true' : 'false'));
        $this->info("getPersonalFolderId(): " . ($user->getPersonalFolderId() ?? 'NULL'));
        
        $this->info('');
        $this->info('=== Testando getOrCreatePersonalFolder() ===');
        try {
            $folderId = $user->getOrCreatePersonalFolder();
            $this->info("Resultado: " . ($folderId ?? 'NULL'));
            
            // Verificar se foi salvo no banco
            $user->refresh();
            $this->info("Campo no banco após criação: " . ($user->google_drive_folder_id ?? 'NULL'));
        } catch (\Exception $e) {
            $this->error("Erro: " . $e->getMessage());
        }
        
        $this->info('');
        $this->info('=== Pastas acessíveis ===');
        try {
            $accessibleFolders = $user->getAccessibleFolderIds();
            $this->info("Total: " . count($accessibleFolders));
            foreach ($accessibleFolders as $folder) {
                $this->info("   - {$folder}");
            }
        } catch (\Exception $e) {
            $this->error("Erro ao obter pastas acessíveis: " . $e->getMessage());
        }
    }
}
