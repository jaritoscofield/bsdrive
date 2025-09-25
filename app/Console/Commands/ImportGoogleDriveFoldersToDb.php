<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;
use App\Models\Folder;
use App\Models\Company;
use App\Models\Sector;
use Illuminate\Support\Facades\Log;

class ImportGoogleDriveFoldersToDb extends Command
{
    protected $signature = 'import:drive-folders';
    protected $description = 'Importa todas as pastas do Google Drive para a tabela folders do banco de dados';

    public function handle()
    {
        $this->info('Iniciando importação de pastas do Google Drive para o banco...');
        $drive = app(GoogleDriveService::class);
        $allFolders = $drive->listFiles(null, 'files(id,name,mimeType,parents)');
        $folders = array_filter($allFolders, function($file) {
            return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
        });
        $folders = array_values($folders);
        $this->info('Total de pastas encontradas: ' . count($folders));
        $empresas = Company::all();
        foreach ($folders as $folder) {
            // Tenta associar à primeira empresa existente
            $empresa = $empresas->first();
            if (!$empresa) {
                $this->error('Nenhuma empresa encontrada no banco.');
                return;
            }
            // Tenta associar ao primeiro setor da empresa
            $sector = Sector::where('company_id', $empresa->id)->first();
            if (!$sector) {
                $this->error('Nenhum setor encontrado para a empresa ' . $empresa->name);
                return;
            }
            // Verifica se já existe no banco
            if (Folder::where('google_drive_id', $folder['id'])->exists()) {
                $this->line('Pasta já existe no banco: ' . $folder['name']);
                continue;
            }
            $folderModel = new Folder();
            $folderModel->name = $folder['name'];
            $folderModel->google_drive_id = $folder['id'];
            $folderModel->parent_id = null; // Não resolve hierarquia agora
            $folderModel->company_id = $empresa->id;
            $folderModel->sector_id = $sector->id;
            $folderModel->path = $folder['name'];
            $folderModel->active = true;
            $folderModel->save();
            $this->info('Pasta importada: ' . $folder['name']);
        }
        $this->info('Importação concluída!');
    }
}
