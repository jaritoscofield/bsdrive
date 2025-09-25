<?php

namespace App\Jobs;

use App\Models\Folder;
use App\Models\Company;
use App\Models\CompanyFolder;
use App\Services\GoogleDriveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoSyncGoogleDriveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleDriveService $googleDriveService): void
    {
        Log::info("🔄 Iniciando job de sincronização automática do Google Drive");

        try {
            // Buscar todas as pastas do Google Drive
            $allFiles = $googleDriveService->listFiles(null, 'files(id,name,mimeType,parents,createdTime)');
            
            // Filtrar apenas pastas
            $folders = collect($allFiles)->filter(function($file) {
                return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
            })->values();

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

                    Log::info("✅ Nova pasta criada: {$folderName} (ID: {$folderId})");
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

                            Log::info("🔗 Pasta '{$folderName}' vinculada à empresa '{$company->name}'");
                            $linked++;
                        }
                    }
                }
            }

            Log::info("📊 Sincronização concluída: {$created} pastas criadas, {$linked} vinculações");

            // Agendar próxima execução em 5 minutos
            if ($created > 0 || $linked > 0) {
                Log::info("🔄 Agendando próxima verificação em 5 minutos");
                self::dispatch()->delay(now()->addMinutes(5));
            } else {
                Log::info("🔄 Agendando próxima verificação em 10 minutos");
                self::dispatch()->delay(now()->addMinutes(10));
            }

        } catch (\Exception $e) {
            Log::error("❌ Erro no job de sincronização: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("❌ Job de sincronização falhou: " . $exception->getMessage());
        
        // Tentar novamente em 15 minutos
        self::dispatch()->delay(now()->addMinutes(15));
    }
} 