<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;

class CheckGoogleConfig extends Command
{
    protected $signature = 'google:check {folderId?}';
    protected $description = 'Testa a autenticação do Google Drive e lista arquivos compartilhados com a Service Account';

    public function handle()
    {
        $service = new GoogleDriveService();
        $this->info('Testando autenticação com Service Account...');
        $serviceAccountFile = config('services.google.service_account_file');
        $this->info('Caminho do service_account_file: ' . $serviceAccountFile);
        $this->info('Arquivo existe? ' . (file_exists($serviceAccountFile) ? 'SIM' : 'NÃO'));

        $folderId = $this->argument('folderId');
        if ($folderId) {
            $this->info('Listando arquivos da pasta ID: ' . $folderId);
            try {
                $arquivos = $service->listFiles($folderId);
                if (empty($arquivos)) {
                    $this->warn('NENHUM arquivo/pasta encontrado nesta pasta!');
                } else {
                    foreach ($arquivos as $file) {
                        $this->line('ID: ' . $file->id . ' | Nome: ' . $file->name . ' | Tipo: ' . $file->mimeType);
                    }
                }
            } catch (\Exception $e) {
                $this->error('Erro ao listar arquivos da pasta: ' . $e->getMessage());
            }
        } else {
            try {
                $arquivos = $service->listSharedWithMe();
                $this->info('Arquivos compartilhados com a Service Account:');
                if (empty($arquivos)) {
                    $this->warn('NENHUM arquivo/pasta compartilhado encontrado!');
                } else {
                    foreach ($arquivos as $file) {
                        $this->line('ID: ' . $file->id . ' | Nome: ' . $file->name . ' | Tipo: ' . $file->mimeType);
                    }
                }
            } catch (\Exception $e) {
                $this->error('Erro ao listar arquivos: ' . $e->getMessage());
            }
        }
    }
}
