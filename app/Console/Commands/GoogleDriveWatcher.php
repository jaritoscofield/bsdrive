<?php

namespace App\Console\Commands;

use App\Models\Folder;
use App\Models\Company;
use App\Models\CompanyFolder;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GoogleDriveWatcher extends Command
{
    protected $signature = 'google-drive:watch {--interval=30}';
    protected $description = 'Monitora continuamente mudanças no Google Drive e sincroniza automaticamente';

    protected $googleDriveService;
    protected $lastSyncTime;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
        $this->lastSyncTime = now()->subMinutes(5); // Iniciar com 5 minutos atrás
    }

    public function handle()
    {
        $interval = $this->option('interval');
        
        $this->info("🔄 Iniciando monitoramento do Google Drive...");
        $this->info("📡 Verificando mudanças a cada {$interval} segundos");
        $this->info("⏰ Última verificação: " . $this->lastSyncTime->format('H:i:s'));
        $this->info("💡 Pressione Ctrl+C para parar");
        $this->info("");

        while (true) {
            try {
                $this->checkForNewFolders();
                sleep($interval);
            } catch (\Exception $e) {
                Log::error("Erro no GoogleDriveWatcher: " . $e->getMessage());
                $this->error("❌ Erro: " . $e->getMessage());
                sleep($interval);
            }
        }
    }

    private function checkForNewFolders()
    {
        $this->info("🔍 Verificando novas pastas... (" . now()->format('H:i:s') . ")");

        try {
            // Buscar todas as pastas do Google Drive
            $allFiles = $this->googleDriveService->listFiles(null, 'files(id,name,mimeType,parents,createdTime,modifiedTime)');
            
            // Filtrar apenas pastas
            $folders = collect($allFiles)->filter(function($file) {
                return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
            })->values();

            $newFolders = 0;
            $linked = 0;

            foreach ($folders as $folder) {
                $folderId = $folder['id'];
                $folderName = $folder['name'];
                $createdTime = isset($folder['createdTime']) ? \Carbon\Carbon::parse($folder['createdTime']) : null;

                // Verificar se é uma pasta nova (criada após a última verificação)
                if ($createdTime && $createdTime->gt($this->lastSyncTime)) {
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

                        $this->line("✅ Nova pasta detectada: {$folderName} (ID: {$folderId})");
                        $newFolders++;

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
                                    'description' => "Pasta detectada automaticamente",
                                    'active' => true,
                                ]);

                                $this->line("   🔗 Vinculada à empresa: {$company->name}");
                                $linked++;
                            }
                        }

                        // Log da descoberta
                        Log::info("Nova pasta '{$folderName}' detectada e vinculada automaticamente às empresas");
                    }
                }
            }

            if ($newFolders > 0) {
                $this->info("📊 Resumo: {$newFolders} novas pastas, {$linked} vinculações");
            } else {
                $this->line("✅ Nenhuma nova pasta encontrada");
            }

            $this->lastSyncTime = now();

        } catch (\Exception $e) {
            Log::error("Erro ao verificar pastas do Google Drive: " . $e->getMessage());
            $this->error("❌ Erro ao verificar: " . $e->getMessage());
        }
    }
} 