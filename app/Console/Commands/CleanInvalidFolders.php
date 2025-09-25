<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Company;
use App\Services\GoogleDriveService;

class CleanInvalidFolders extends Command
{
    protected $signature = 'google:clean-invalid-folders';
    protected $description = 'Remove referências de pastas que não existem mais no Google Drive';

    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $this->info('🧹 Limpando referências de pastas inválidas...');
        
        $cleanedUsers = 0;
        $cleanedCompanies = 0;
        
        // Limpar pastas de usuários
        $users = User::whereNotNull('google_drive_folder_id')->get();
        foreach ($users as $user) {
            if (!$this->googleDriveService->fileExists($user->google_drive_folder_id)) {
                $this->warn("❌ Usuário {$user->name} (ID: {$user->id}) - Pasta {$user->google_drive_folder_id} não existe");
                $user->google_drive_folder_id = null;
                $user->save();
                $cleanedUsers++;
            } else {
                $this->line("✅ Usuário {$user->name} - Pasta válida");
            }
        }
        
        // Limpar pastas de empresas (verificar se a coluna existe)
        try {
            $companies = Company::whereNotNull('google_drive_folder_id')->get();
            foreach ($companies as $company) {
                if (!$this->googleDriveService->fileExists($company->google_drive_folder_id)) {
                    $this->warn("❌ Empresa {$company->name} (ID: {$company->id}) - Pasta {$company->google_drive_folder_id} não existe");
                    $company->google_drive_folder_id = null;
                    $company->save();
                    $cleanedCompanies++;
                } else {
                    $this->line("✅ Empresa {$company->name} - Pasta válida");
                }
            }
        } catch (\Exception $e) {
            $this->warn("⚠️ Empresas não possuem campo google_drive_folder_id - pulando...");
        }
        
        $this->info("🎯 Limpeza concluída!");
        $this->info("📊 Usuários corrigidos: {$cleanedUsers}");
        $this->info("🏢 Empresas corrigidas: {$cleanedCompanies}");
        
        return 0;
    }
}
