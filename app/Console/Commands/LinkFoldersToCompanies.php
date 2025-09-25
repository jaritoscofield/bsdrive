<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Folder;
use App\Models\CompanyFolder;
use Illuminate\Console\Command;

class LinkFoldersToCompanies extends Command
{
    protected $signature = 'folders:link-to-companies {--company-id=} {--folder-id=}';
    protected $description = 'Vincula pastas às empresas para permitir acesso aos usuários';

    public function handle()
    {
        $companyId = $this->option('company-id');
        $folderId = $this->option('folder-id');

        if ($companyId && $folderId) {
            // Vincular pasta específica à empresa específica
            $this->linkSpecificFolder($companyId, $folderId);
        } else {
            // Vincular todas as pastas a todas as empresas
            $this->linkAllFolders();
        }
    }

    private function linkSpecificFolder($companyId, $folderId)
    {
        $company = Company::find($companyId);
        $folder = Folder::find($folderId);

        if (!$company) {
            $this->error("Empresa com ID {$companyId} não encontrada.");
            return;
        }

        if (!$folder) {
            $this->error("Pasta com ID {$folderId} não encontrada.");
            return;
        }

        // Verificar se já existe o vínculo
        $existing = CompanyFolder::where('company_id', $companyId)
            ->where('google_drive_folder_id', $folder->google_drive_id)
            ->first();

        if ($existing) {
            $this->warn("Vínculo já existe entre empresa '{$company->name}' e pasta '{$folder->name}'.");
            return;
        }

        // Criar o vínculo
        CompanyFolder::create([
            'company_id' => $companyId,
            'google_drive_folder_id' => $folder->google_drive_id,
            'folder_name' => $folder->name,
            'description' => "Pasta vinculada automaticamente",
            'active' => true,
        ]);

        $this->info("Vínculo criado entre empresa '{$company->name}' e pasta '{$folder->name}'.");
    }

    private function linkAllFolders()
    {
        $companies = Company::where('active', true)->get();
        $folders = Folder::where('active', true)->get();

        if ($companies->isEmpty()) {
            $this->error("Nenhuma empresa ativa encontrada.");
            return;
        }

        if ($folders->isEmpty()) {
            $this->error("Nenhuma pasta ativa encontrada.");
            return;
        }

        $this->info("Vinculando pastas às empresas...");
        $this->info("Empresas: " . $companies->count());
        $this->info("Pastas: " . $folders->count());

        $linked = 0;
        $skipped = 0;

        foreach ($companies as $company) {
            foreach ($folders as $folder) {
                // Verificar se já existe o vínculo
                $existing = CompanyFolder::where('company_id', $company->id)
                    ->where('google_drive_folder_id', $folder->google_drive_id)
                    ->first();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                // Criar o vínculo
                CompanyFolder::create([
                    'company_id' => $company->id,
                    'google_drive_folder_id' => $folder->google_drive_id,
                    'folder_name' => $folder->name,
                    'description' => "Pasta vinculada automaticamente",
                    'active' => true,
                ]);

                $linked++;
                $this->line("✓ Vinculado: {$company->name} → {$folder->name}");
            }
        }

        $this->info("Processo concluído!");
        $this->info("Vínculos criados: {$linked}");
        $this->info("Vínculos ignorados (já existiam): {$skipped}");
    }
} 