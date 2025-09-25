<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CompanyFolder;
use App\Services\GoogleDriveService;

class LinkExistingSubfolders extends Command
{
    protected $signature = 'link:existing-subfolders {company_id?} {--folder-id=}';
    protected $description = 'Vincula subpastas existentes no Google Drive que não estão na tabela company_folders';

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $companyId = $this->argument('company_id');
        $folderId = $this->option('folder-id');
        
        if (!$companyId) {
            $companyId = $this->ask('Digite o ID da empresa:');
        }
        
        if (!$folderId) {
            $folderId = $this->ask('Digite o ID da pasta raiz (pasta "pasta"):');
        }
        
        $this->info("🔗 Vinculando subpastas da empresa {$companyId} na pasta {$folderId}");
        $this->newLine();
        
        try {
            // Buscar todas as subpastas da pasta raiz
            $allItems = $this->googleDriveService->listFiles($folderId, 'files(id,name,mimeType,parents)');
            $subfolders = array_filter($allItems, function($item) {
                return isset($item['mimeType']) && $item['mimeType'] === 'application/vnd.google-apps.folder';
            });
            
            $this->info("📁 Subpastas encontradas no Google Drive:");
            foreach ($subfolders as $subfolder) {
                $this->line("   📂 {$subfolder['name']} (ID: {$subfolder['id']})");
            }
            $this->newLine();
            
            // Verificar quais já estão vinculadas
            $existingFolderIds = CompanyFolder::where('company_id', $companyId)
                ->pluck('google_drive_folder_id')
                ->toArray();
            
            $this->info("🔍 Verificando vínculos existentes...");
            $linked = 0;
            $alreadyLinked = 0;
            
            foreach ($subfolders as $subfolder) {
                if (in_array($subfolder['id'], $existingFolderIds)) {
                    $this->line("   ✅ {$subfolder['name']} - Já vinculada");
                    $alreadyLinked++;
                } else {
                    // Vincular a subpasta
                    CompanyFolder::create([
                        'company_id' => $companyId,
                        'google_drive_folder_id' => $subfolder['id'],
                        'folder_name' => $subfolder['name'],
                        'description' => 'Subpasta vinculada manualmente',
                        'active' => true,
                    ]);
                    
                    $this->line("   🔗 {$subfolder['name']} - Vinculada agora");
                    $linked++;
                }
            }
            
            $this->newLine();
            $this->info("📊 Resumo:");
            $this->line("   Subpastas já vinculadas: {$alreadyLinked}");
            $this->line("   Subpastas vinculadas agora: {$linked}");
            $this->line("   Total de subpastas: " . count($subfolders));
            
            if ($linked > 0) {
                $this->info("✅ Processo concluído com sucesso!");
            } else {
                $this->warn("⚠️ Nenhuma subpasta nova foi vinculada.");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
} 