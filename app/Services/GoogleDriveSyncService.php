<?php

namespace App\Services;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class GoogleDriveSyncService
{
    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Sincroniza uma pasta local com o Google Drive
     */
    public function syncFolder(Folder $folder, $googleDriveFolderId = null)
    {
        try {
            Log::info('Iniciando sincronização de pasta', [
                'folder_id' => $folder->id,
                'folder_name' => $folder->name,
                'google_drive_folder_id' => $googleDriveFolderId
            ]);

            // Se não tem ID do Google Drive, cria a pasta
            if (!$googleDriveFolderId) {
                $googleDriveFolder = $this->googleDriveService->createFolder(
                    $folder->name,
                    $folder->parent ? $folder->parent->google_drive_id : null
                );

                $folder->update(['google_drive_id' => $googleDriveFolder->getId()]);
                $googleDriveFolderId = $googleDriveFolder->getId();

                Log::info('Pasta criada no Google Drive', [
                    'folder_id' => $folder->id,
                    'google_drive_id' => $googleDriveFolderId
                ]);
            }

            // Sincroniza subpastas
            foreach ($folder->children()->notDeleted()->get() as $childFolder) {
                $this->syncFolder($childFolder, $childFolder->google_drive_id);
            }

            // Sincroniza arquivos
            foreach ($folder->files()->notDeleted()->get() as $file) {
                $this->syncFile($file, $googleDriveFolderId);
            }

            Log::info('Sincronização de pasta concluída', [
                'folder_id' => $folder->id,
                'google_drive_folder_id' => $googleDriveFolderId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erro na sincronização de pasta', [
                'folder_id' => $folder->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sincroniza um arquivo local com o Google Drive
     */
    public function syncFile(File $file, $googleDriveFolderId = null)
    {
        try {
            Log::info('Iniciando sincronização de arquivo', [
                'file_id' => $file->id,
                'file_name' => $file->name,
                'google_drive_folder_id' => $googleDriveFolderId
            ]);

            // Se não tem ID do Google Drive, faz upload
            if (!$file->google_drive_id) {
                if ($file->exists()) {
                    $googleDriveFile = $this->googleDriveService->uploadFile(
                        $file->getStoragePath(),
                        $file->original_name,
                        $googleDriveFolderId,
                        $file->mime_type
                    );

                    $file->update(['google_drive_id' => $googleDriveFile->getId()]);

                    Log::info('Arquivo enviado para o Google Drive', [
                        'file_id' => $file->id,
                        'google_drive_id' => $googleDriveFile->getId()
                    ]);
                } else {
                    Log::warning('Arquivo local não encontrado', [
                        'file_id' => $file->id,
                        'file_path' => $file->path
                    ]);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Erro na sincronização de arquivo', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sincroniza todos os dados de uma empresa com o Google Drive
     */
    public function syncCompany($companyId)
    {
        try {
            Log::info('Iniciando sincronização da empresa', ['company_id' => $companyId]);

            $folders = Folder::notDeleted()
                           ->byCompany($companyId)
                           ->root()
                           ->get();

            foreach ($folders as $folder) {
                $this->syncFolder($folder);
            }

            Log::info('Sincronização da empresa concluída', ['company_id' => $companyId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Erro na sincronização da empresa', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Importa dados do Google Drive para o sistema local
     */
    public function importFromGoogleDrive($googleDriveFolderId, $localFolderId = null, $companyId = null)
    {
        try {
            Log::info('Iniciando importação do Google Drive', [
                'google_drive_folder_id' => $googleDriveFolderId,
                'local_folder_id' => $localFolderId,
                'company_id' => $companyId
            ]);

            $items = $this->googleDriveService->listFiles($googleDriveFolderId);

            foreach ($items as $item) {
                if ($this->googleDriveService->isFolder($item->getMimeType())) {
                    // É uma pasta
                    $localFolder = Folder::create([
                        'name' => $item->getName(),
                        'description' => 'Importado do Google Drive',
                        'parent_id' => $localFolderId,
                        'company_id' => $companyId,
                        'google_drive_id' => $item->getId(),
                        'active' => true
                    ]);

                    // Recursivamente importa o conteúdo da pasta
                    $this->importFromGoogleDrive($item->getId(), $localFolder->id, $companyId);
                } else {
                    // É um arquivo
                    $file = File::create([
                        'name' => $item->getName(),
                        'original_name' => $item->getName(),
                        'path' => 'google_drive/' . $item->getId(),
                        'mime_type' => $item->getMimeType(),
                        'size' => $item->getSize() ?? 0,
                        'description' => 'Importado do Google Drive',
                        'folder_id' => $localFolderId,
                        'company_id' => $companyId,
                        'google_drive_id' => $item->getId(),
                        'uploaded_by' => Auth::id(),
                        'active' => true
                    ]);
                }
            }

            Log::info('Importação do Google Drive concluída', [
                'google_drive_folder_id' => $googleDriveFolderId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erro na importação do Google Drive', [
                'google_drive_folder_id' => $googleDriveFolderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza um arquivo no Google Drive quando alterado localmente
     */
    public function updateFileInGoogleDrive(File $file)
    {
        try {
            if (!$file->google_drive_id) {
                return $this->syncFile($file, $file->folder?->google_drive_id);
            }

            if ($file->exists()) {
                $this->googleDriveService->updateFile(
                    $file->google_drive_id,
                    $file->getStoragePath(),
                    $file->original_name,
                    $file->mime_type
                );

                Log::info('Arquivo atualizado no Google Drive', [
                    'file_id' => $file->id,
                    'google_drive_id' => $file->google_drive_id
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar arquivo no Google Drive', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove um arquivo do Google Drive quando deletado localmente
     */
    public function deleteFileFromGoogleDrive(File $file)
    {
        try {
            if ($file->google_drive_id) {
                $this->googleDriveService->deleteFile($file->google_drive_id);

                Log::info('Arquivo removido do Google Drive', [
                    'file_id' => $file->id,
                    'google_drive_id' => $file->google_drive_id
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao remover arquivo do Google Drive', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove uma pasta do Google Drive quando deletada localmente
     */
    public function deleteFolderFromGoogleDrive(Folder $folder)
    {
        try {
            if ($folder->google_drive_id) {
                $this->googleDriveService->deleteFile($folder->google_drive_id);

                Log::info('Pasta removida do Google Drive', [
                    'folder_id' => $folder->id,
                    'google_drive_id' => $folder->google_drive_id
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao remover pasta do Google Drive', [
                'folder_id' => $folder->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
