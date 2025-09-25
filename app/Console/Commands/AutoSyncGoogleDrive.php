<?php

namespace App\Console\Commands;

use App\Models\Folder;
use App\Models\Company;
use App\Models\CompanyFolder;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class AutoSyncGoogleDrive extends Command
{
    protected $signature = 'sync:auto-google-drive';
    protected $description = 'Sincronização automática de pastas do Google Drive';

    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $this->info('🔄 Iniciando sincronização automática...');

        try {
            // Buscar todas as pastas do Google Drive
            $allFiles = $this->googleDriveService->listFiles(null, 'files(id,name,mimeType,parents,createdTime)');
            
            // Filtrar apenas pastas
            $folders = collect($allFiles)->filter(function($file) {
                return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
            })->values();

            $this->info("📁 Encontradas {$folders->count()} pastas no Google Drive");

            $created = 0;
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
                    $newFolder->company_id = 2; // Empresa padrão
                    $newFolder->sector_id = null;
                    $newFolder->parent_id = $folder['parents'][0] ?? null;
                    $newFolder->path = $folderName;
                    $newFolder->active = true;
                    $newFolder->save();

                    $this->line("✅ Criada: {$folderName} (ID: {$folderId})");
                    $created++;

                    // Vincular automaticamente a todas as empresas ativas
                    $companies = Company::where('active', true)->get();
                    foreach ($companies as $company) {
                        $existing = CompanyFolder::where('company_id', $company->id)
                            ->where('google_drive_folder_id', $folderId)
                            ->first();

                        if (!$existing) {
                            CompanyFolder::create([
                                'company_id' => $company->id,
                                'google_drive_folder_id' => $folderId,
                                'folder_name' => $folderName,
                                'description' => "Pasta sincronizada automaticamente",
                                'active' => true,
                            ]);

                            $this->line("   🔗 Vinculada à empresa: {$company->name}");
                            $linked++;
                        }
                    }
                }
            }

            $this->info('');
            $this->info('📊 Resumo da sincronização automática:');
            $this->info("   ✅ Pastas criadas: {$created}");
            $this->info("   🔗 Pastas vinculadas: {$linked}");

            if ($created > 0 || $linked > 0) {
                $this->info('🎉 Sincronização automática concluída com sucesso!');
            } else {
                $this->info('✅ Nenhuma nova pasta encontrada para sincronizar.');
            }

        } catch (\Exception $e) {
            $this->error("❌ Erro durante a sincronização automática: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 