<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CompanyFolder;
use App\Services\GoogleDriveService;

class CleanupDeletedFolders extends Command
{
    protected $signature = 'cleanup:deleted-folders {--company-id=} {--dry-run}';
    protected $description = 'Remove referências de pastas que foram deletadas do Google Drive';

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $companyId = $this->option('company-id');
        $dryRun = $this->option('dry-run');
        
        $this->info("🧹 Limpando pastas deletadas do Google Drive...");
        if ($dryRun) {
            $this->warn("🔍 Modo DRY-RUN - nenhuma alteração será feita");
        }
        $this->newLine();
        
        $query = CompanyFolder::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        $companyFolders = $query->get();
        
        if ($companyFolders->isEmpty()) {
            $this->warn("Nenhuma pasta encontrada para verificar.");
            return 0;
        }
        
        $this->info("📋 Verificando {$companyFolders->count()} pastas...");
        $this->newLine();
        
        $deleted = 0;
        $exists = 0;
        
        foreach ($companyFolders as $companyFolder) {
            $exists = $this->googleDriveService->fileExists($companyFolder->google_drive_folder_id);
            
            if (!$exists) {
                $this->line("   ❌ {$companyFolder->folder_name} (ID: {$companyFolder->google_drive_folder_id}) - DELETADA");
                $deleted++;
                
                if (!$dryRun) {
                    $companyFolder->delete();
                    $this->line("      🗑️ Removida da tabela company_folders");
                }
            } else {
                $this->line("   ✅ {$companyFolder->folder_name} (ID: {$companyFolder->google_drive_folder_id}) - EXISTE");
                $exists++;
            }
        }
        
        $this->newLine();
        $this->info("📊 Resumo:");
        $this->line("   Pastas que existem: {$exists}");
        $this->line("   Pastas deletadas: {$deleted}");
        
        if ($deleted > 0 && !$dryRun) {
            $this->info("✅ Limpeza concluída! {$deleted} pastas removidas.");
        } elseif ($deleted > 0 && $dryRun) {
            $this->warn("⚠️ {$deleted} pastas seriam removidas (use --dry-run=false para executar)");
        } else {
            $this->info("✅ Nenhuma pasta deletada encontrada.");
        }
        
        return 0;
    }
} 