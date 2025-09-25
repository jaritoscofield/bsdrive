<?php

namespace App\Listeners;

use App\Models\Folder;
use App\Models\Company;
use App\Models\CompanyFolder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AutoLinkFolderToCompanies
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        // Verificar se é um evento de criação de pasta
        if ($event instanceof Folder && $event->wasRecentlyCreated) {
            $this->linkFolderToCompanies($event);
        }
    }

    /**
     * Vincula uma pasta automaticamente a todas as empresas ativas
     */
    private function linkFolderToCompanies(Folder $folder): void
    {
        $companies = Company::where('active', true)->get();

        foreach ($companies as $company) {
            try {
                // Verificar se já existe o vínculo
                $existing = CompanyFolder::where('company_id', $company->id)
                    ->where('google_drive_folder_id', $folder->google_drive_id)
                    ->first();

                if (!$existing) {
                    CompanyFolder::create([
                        'company_id' => $company->id,
                        'google_drive_folder_id' => $folder->google_drive_id,
                        'folder_name' => $folder->name,
                        'description' => "Pasta vinculada automaticamente",
                        'active' => true,
                    ]);

                    \Log::info("Pasta '{$folder->name}' vinculada automaticamente à empresa '{$company->name}'");
                } else {
                    \Log::info("Vínculo já existe para pasta '{$folder->name}' e empresa '{$company->name}'");
                }
            } catch (\Exception $e) {
                \Log::error("Erro ao vincular pasta '{$folder->name}' à empresa '{$company->name}': " . $e->getMessage());
            }
        }
    }
} 