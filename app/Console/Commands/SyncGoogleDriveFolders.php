<?php

namespace App\Console\Commands;

use App\Models\Folder;
use App\Models\Company;
use App\Models\CompanyFolder;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class SyncGoogleDriveFolders extends Command
{
    protected $signature = 'sync:google-drive-folders {--company-id=} {--auto-link}';
    protected $description = 'Sincroniza pastas do Google Drive com o banco de dados e vincula às empresas';

    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $this->info('🔄 Iniciando sincronização de pastas do Google Drive...');

        try {
            // Buscar todas as pastas do Google Drive
            $allFiles = $this->googleDriveService->listFiles(null, 'files(id,name,mimeType,parents,createdTime)');
            
            // Filtrar apenas pastas
            $folders = collect($allFiles)->filter(function($file) {
                return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
            })->values();

            $this->info("📁 Encontradas {$folders->count()} pastas no Google Drive");

            $created = 0;
            $updated = 0;
            $linked = 0;

            foreach ($folders as $folder) {
                $folderId = $folder['id'];
                $folderName = $folder['name'];

                // Verificar se a pasta já existe no banco
                $existingFolder = Folder::where('google_drive_id', $folderId)->first();

                if (!$existingFolder) {
                    // Criar nova pasta no banco
                    $newFolder = new Folder();
                    $newFolder->name = $folderName;
                    $newFolder->google_drive_id = $folderId;
                    $newFolder->company_id = 2; // Empresa padrão (você pode ajustar)
                    $newFolder->sector_id = null; // Setor nullable
                    $newFolder->parent_id = $folder['parents'][0] ?? null;
                    $newFolder->path = $folderName;
                    $newFolder->active = true;
                    $newFolder->save();

                    $this->line("✅ Criada: {$folderName} (ID: {$folderId})");
                    $created++;

                    // Se a opção auto-link está ativada, vincular automaticamente às empresas
                    if ($this->option('auto-link')) {
                        $this->linkFolderToCompanies($newFolder);
                        $linked++;
                    }
                } else {
                    // Atualizar pasta existente
                    $existingFolder->name = $folderName;
                    $existingFolder->parent_id = $folder['parents'][0] ?? null;
                    $existingFolder->path = $folderName;
                    $existingFolder->save();

                    $this->line("🔄 Atualizada: {$folderName} (ID: {$folderId})");
                    $updated++;
                }
            }

            $this->info('');
            $this->info('📊 Resumo da sincronização:');
            $this->info("   ✅ Pastas criadas: {$created}");
            $this->info("   🔄 Pastas atualizadas: {$updated}");
            $this->info("   🔗 Pastas vinculadas: {$linked}");

            // Se especificou uma empresa, vincular apenas a ela
            if ($companyId = $this->option('company-id')) {
                $this->linkFolderToSpecificCompany($companyId);
            }

            $this->info('🎉 Sincronização concluída com sucesso!');

        } catch (\Exception $e) {
            $this->error("❌ Erro durante a sincronização: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function linkFolderToCompanies($folder)
    {
        $companies = Company::where('active', true)->get();

        foreach ($companies as $company) {
            // Verificar se já existe o vínculo
            $existing = CompanyFolder::where('company_id', $company->id)
                ->where('google_drive_folder_id', $folder->google_drive_id)
                ->first();

            if (!$existing) {
                CompanyFolder::create([
                    'company_id' => $company->id,
                    'google_drive_folder_id' => $folder->google_drive_id,
                    'folder_name' => $folder->name,
                    'description' => "Pasta sincronizada automaticamente",
                    'active' => true,
                ]);

                $this->line("   🔗 Vinculada à empresa: {$company->name}");
            }
        }
    }

    private function linkFolderToSpecificCompany($companyId)
    {
        $company = Company::find($companyId);
        if (!$company) {
            $this->error("❌ Empresa com ID {$companyId} não encontrada.");
            return;
        }

        $this->info("🔗 Vinculando pastas à empresa: {$company->name}");

        $folders = Folder::where('active', true)->get();
        $linked = 0;

        foreach ($folders as $folder) {
            $existing = CompanyFolder::where('company_id', $company->id)
                ->where('google_drive_folder_id', $folder->google_drive_id)
                ->first();

            if (!$existing) {
                CompanyFolder::create([
                    'company_id' => $company->id,
                    'google_drive_folder_id' => $folder->google_drive_id,
                    'folder_name' => $folder->name,
                    'description' => "Pasta vinculada manualmente",
                    'active' => true,
                ]);

                $this->line("   ✅ Vinculada: {$folder->name}");
                $linked++;
            }
        }

        $this->info("📊 {$linked} pastas vinculadas à empresa {$company->name}");
    }
} 