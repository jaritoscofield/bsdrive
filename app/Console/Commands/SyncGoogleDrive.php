<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\File;
use App\Models\Folder;
use App\Services\GoogleDriveSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGoogleDrive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-drive:sync
                            {--company= : ID da empresa específica para sincronizar}
                            {--type=all : Tipo de sincronização (all, files, folders)}
                            {--force : Forçar sincronização mesmo se já sincronizado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza dados com o Google Drive';

    private $syncService;

    /**
     * Create a new command instance.
     */
    public function __construct(GoogleDriveSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronização com Google Drive...');

        $companyId = $this->option('company');
        $type = $this->option('type');
        $force = $this->option('force');

        try {
            if ($companyId) {
                $this->syncCompany($companyId, $type, $force);
            } else {
                $this->syncAllCompanies($type, $force);
            }

            $this->info('Sincronização concluída com sucesso!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Erro durante a sincronização: ' . $e->getMessage());
            Log::error('Google Drive sync error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Sincroniza uma empresa específica
     */
    private function syncCompany($companyId, $type, $force)
    {
        $company = Company::find($companyId);
        if (!$company) {
            throw new \Exception("Empresa com ID {$companyId} não encontrada");
        }

        $this->info("Sincronizando empresa: {$company->name}");

        if ($type === 'all' || $type === 'folders') {
            $this->syncCompanyFolders($company, $force);
        }

        if ($type === 'all' || $type === 'files') {
            $this->syncCompanyFiles($company, $force);
        }
    }

    /**
     * Sincroniza todas as empresas
     */
    private function syncAllCompanies($type, $force)
    {
        $companies = Company::all();
        $this->info("Sincronizando {$companies->count()} empresas");

        $progressBar = $this->output->createProgressBar($companies->count());
        $progressBar->start();

        foreach ($companies as $company) {
            try {
                if ($type === 'all' || $type === 'folders') {
                    $this->syncCompanyFolders($company, $force);
                }

                if ($type === 'all' || $type === 'files') {
                    $this->syncCompanyFiles($company, $force);
                }
            } catch (\Exception $e) {
                $this->warn("Erro ao sincronizar empresa {$company->name}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Sincroniza pastas de uma empresa
     */
    private function syncCompanyFolders($company, $force)
    {
        $folders = Folder::notDeleted()->byCompany($company->id);

        if (!$force) {
            $folders = $folders->whereNull('google_drive_id');
        }

        $folders = $folders->get();

        if ($folders->isEmpty()) {
            $this->line("Nenhuma pasta para sincronizar na empresa {$company->name}");
            return;
        }

        $this->line("Sincronizando {$folders->count()} pastas...");

        $progressBar = $this->output->createProgressBar($folders->count());
        $progressBar->start();

        foreach ($folders as $folder) {
            try {
                $this->syncService->syncFolder($folder);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->warn("Erro ao sincronizar pasta {$folder->name}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Sincroniza arquivos de uma empresa
     */
    private function syncCompanyFiles($company, $force)
    {
        $files = File::notDeleted()->byCompany($company->id);

        if (!$force) {
            $files = $files->whereNull('google_drive_id');
        }

        $files = $files->get();

        if ($files->isEmpty()) {
            $this->line("Nenhum arquivo para sincronizar na empresa {$company->name}");
            return;
        }

        $this->line("Sincronizando {$files->count()} arquivos...");

        $progressBar = $this->output->createProgressBar($files->count());
        $progressBar->start();

        foreach ($files as $file) {
            try {
                $this->syncService->syncFile($file, $file->folder?->google_drive_id);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->warn("Erro ao sincronizar arquivo {$file->name}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine();
    }
}
