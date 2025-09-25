<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleServiceDrive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class GoogleDriveService
{
    private $client;
    private $service;
    private $apiKey;
    private $sharedDriveId;
    private $rootFolderId;

    public function __construct()
    {
        $this->apiKey = config('services.google.api_key');
        // Desabilitado: $this->sharedDriveId = config('services.google.shared_drive_id');
        $this->sharedDriveId = null; // Forçar para null para não usar shared drives
        $this->rootFolderId = env('GOOGLE_DRIVE_ROOT_FOLDER_ID');
        $this->initializeClient();
    }

    private function initializeClient()
    {
        $this->client = new GoogleClient();
        $serviceAccountFile = config('services.google.service_account_file');
        \Log::info('DEBUG: Caminho do service_account_file', [
            'service_account_file' => $serviceAccountFile,
            'file_exists' => file_exists($serviceAccountFile)
        ]);
        if ($serviceAccountFile && file_exists($serviceAccountFile)) {
            $this->client->setAuthConfig($serviceAccountFile);
            $this->client->setScopes([GoogleServiceDrive::DRIVE]);
            $this->service = new GoogleServiceDrive($this->client);
            return;
        }
        // Fallback para API Key (apenas leitura)
        $this->apiKey = config('services.google.api_key');
        $this->client->setDeveloperKey($this->apiKey);
        $this->service = new GoogleServiceDrive($this->client);
    }

    /**
     * Lista arquivos compartilhados com a Service Account (debug)
     */
    public function listSharedWithMe($fields = 'files(id,name,mimeType,size,createdTime,modifiedTime,parents)')
    {
        try {
            $query = 'sharedWithMe = true';
            $optParams = [
                'q' => $query,
                'fields' => $fields,
                'orderBy' => 'name'
            ];
            $results = $this->service->files->listFiles($optParams);
            return $results->getFiles();
        } catch (\Exception $e) {
            \Log::error('Google Drive API Error - listSharedWithMe', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Lista arquivos e pastas de um diretório específico ou por query customizada
     */
    public function listFiles($folderId = null, $fields = 'files(id,name,mimeType,size,createdTime,modifiedTime,parents)', $customQuery = null)
    {
        $folderId = $folderId ?? $this->rootFolderId;
        \Log::info('GoogleDriveService::listFiles chamado', [
            'folderId' => $folderId,
            'fields' => $fields,
            'user_id' => \Auth::check() ? \Auth::id() : null,
            'customQuery' => $customQuery
        ]);
        try {
            $optParams = [
                'fields' => $fields,
                'orderBy' => 'name',
                // Removido: 'supportsAllDrives' => true,
                // Removido: 'includeItemsFromAllDrives' => true,
            ];
            
            // Não usar shared drives - comentado o bloco abaixo
            /*
            if ($this->sharedDriveId) {
                $optParams['driveId'] = $this->sharedDriveId;
                $optParams['corpora'] = 'drive';
            }
            */

            if ($customQuery) {
                $query = $customQuery;
            } elseif ($folderId) {
                $query = "'{$folderId}' in parents and trashed = false";
            } else {
                $query = "trashed = false";
            }

            \Log::info('GoogleDriveService::listFiles query montada', [
                'query' => $query,
                'optParams' => $optParams
            ]);
            $optParams['q'] = $query;

            $results = $this->service->files->listFiles($optParams);
            $files = $results->getFiles();

            \Log::info('GoogleDriveService::listFiles sucesso', [
                'folderId' => $folderId,
                'total_files' => is_array($files) ? count($files) : 0,
                'files' => $files
            ]);

            return $files;
        } catch (\Google_Service_Exception $e) {
            \Log::error('Google Drive API Error - listFiles', [
                'error' => $e->getMessage(),
                'folder_id' => $folderId,
                'optParams' => $optParams
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Google Drive API Error - listFiles', [
                'error' => $e->getMessage(),
                'folder_id' => $folderId,
                'optParams' => $optParams
            ]);
            throw $e;
        }
    }

    /**
     * Busca uma pasta específica por ID
     */
    public function getFolder($folderId)
    {
        try {
            return $this->service->files->get($folderId, ['fields' => 'id,name,mimeType,parents']);
        } catch (\Exception $e) {
            Log::error('Google Drive API Error - getFolder', [
                'error' => $e->getMessage(),
                'folder_id' => $folderId
            ]);
            throw $e;
        }
    }

    /**
     * Busca um arquivo específico por ID
     */
    public function getFile($fileId)
    {
        try {
            return $this->service->files->get($fileId, ['fields' => 'id,name,mimeType,size,createdTime,modifiedTime,parents,webContentLink']);
        } catch (\Exception $e) {
            Log::error('Google Drive API Error - getFile', [
                'error' => $e->getMessage(),
                'file_id' => $fileId
            ]);
            throw $e;
        }
    }

    /**
     * Cria uma nova pasta no Google Drive
     */
    public function createFolder($name, $parentId = null)
    {
        $parentId = $parentId ?? $this->rootFolderId;
        \Log::info('GoogleDriveService::createFolder chamado', [
            'name' => $name,
            'parentId' => $parentId
        ]);
        try {
            $fileMetadata = new DriveFile([
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);

            if ($parentId) {
                $fileMetadata->setParents([$parentId]);
            }

            $optParams = [
                'fields' => 'id,name,mimeType,parents',
                'supportsAllDrives' => true,
            ];
            \Log::info('GoogleDriveService::createFolder optParams', [
                'fileMetadata' => $fileMetadata,
                'optParams' => $optParams
            ]);

            $folder = $this->service->files->create($fileMetadata, $optParams);

            \Log::info('GoogleDriveService::createFolder sucesso', [
                'folder' => $folder
            ]);

            return $folder;
        } catch (\Exception $e) {
            \Log::error('Google Drive API Error - createFolder', [
                'error' => $e->getMessage(),
                'name' => $name,
                'parent_id' => $parentId
            ]);
            throw $e;
        }
    }

    /**
     * Faz upload de um arquivo para o Google Drive
     */
    public function uploadFile($filePath, $fileName, $parentId = null, $mimeType = null)
    {
        $parentId = $parentId ?? $this->rootFolderId;
        \Log::emergency('🚀 GoogleDriveService::uploadFile chamado', [
            'filePath' => $filePath,
            'fileName' => $fileName,
            'parentId' => $parentId,
            'mimeType' => $mimeType,
            'sharedDriveId' => 'DESABILITADO - Não usando shared drives'
        ]);
        
        // Shared Drive desabilitado - usando drive pessoal
        \Log::emergency('📤 Upload configurado para DRIVE PESSOAL (sem shared drive)');
        \Log::emergency('💡 Shared drives foram desabilitados nesta configuração');
        
        try {
            if (!$mimeType) {
                $mimeType = mime_content_type($filePath);
            }

            $fileMetadata = new DriveFile([
                'name' => $fileName,
                'parents' => [$parentId]
            ]);

            $content = file_get_contents($filePath);

            // Configurar parâmetros SEM Shared Drive
            $optParams = [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id,name,mimeType,size,createdTime,modifiedTime,parents,webContentLink'
                // Removido: 'supportsAllDrives' => true,
            ];
            
            \Log::emergency('📤 Upload para DRIVE PESSOAL (shared drives desabilitados)');
            
            \Log::emergency('📤 Iniciando upload', [
                'fileName' => $fileName,
                'parentId' => $parentId,
                'hasSharedDrive' => false, // Sempre false agora
                'sharedDriveId' => 'DESABILITADO',
                'driveType' => 'DRIVE PESSOAL',
                'optParams' => array_keys($optParams) // Só as chaves para não logar o conteúdo do arquivo
            ]);

            $file = $this->service->files->create($fileMetadata, $optParams);

            \Log::emergency('✅ Upload realizado com sucesso!', [
                'fileId' => $file->getId(),
                'fileName' => $file->getName(),
                'uploadType' => !empty($this->sharedDriveId) ? 'Shared Drive' : 'Drive Pessoal'
            ]);

            return $file;
        } catch (\Exception $e) {
            \Log::emergency('❌ Erro no upload', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
                'file_name' => $fileName,
                'parent_id' => $parentId,
                'shared_drive_id' => $this->sharedDriveId
            ]);
            throw $e;
        }
    }

    /**
     * Download de arquivo pelo ID
     */
    public function downloadFile($fileId)
    {
        \Log::info('GoogleDriveService::downloadFile chamado', [
            'fileId' => $fileId
        ]);
        try {
            $response = $this->service->files->get($fileId, [
                'alt' => 'media',
                'supportsAllDrives' => true,
            ]);
            \Log::info('GoogleDriveService::downloadFile sucesso', [
                'fileId' => $fileId
            ]);
            return $response;
        } catch (\Exception $e) {
            \Log::error('Google Drive API Error - downloadFile', [
                'error' => $e->getMessage(),
                'file_id' => $fileId
            ]);
            throw $e;
        }
    }

    /**
     * Renomear arquivo ou pasta
     */
    public function renameFile($fileId, $newName)
    {
        \Log::info('GoogleDriveService::renameFile chamado', [
            'fileId' => $fileId,
            'newName' => $newName
        ]);
        try {
            $file = new DriveFile([
                'name' => $newName
            ]);
            $updatedFile = $this->service->files->update($fileId, $file, [
                'fields' => 'id,name',
                'supportsAllDrives' => true,
            ]);
            \Log::info('GoogleDriveService::renameFile sucesso', [
                'fileId' => $fileId,
                'newName' => $newName
            ]);
            return $updatedFile;
        } catch (\Exception $e) {
            \Log::error('Google Drive API Error - renameFile', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
                'new_name' => $newName
            ]);
            throw $e;
        }
    }

    /**
     * Soft delete (move para lixeira)
     */
    public function deleteFile($fileId)
    {
        \Log::info('GoogleDriveService::deleteFile chamado', [
            'fileId' => $fileId
        ]);
        try {
            $deletedFile = $this->service->files->update($fileId, new DriveFile(['trashed' => true]), [
                'fields' => 'id,trashed',
                'supportsAllDrives' => true,
            ]);
            \Log::info('GoogleDriveService::deleteFile sucesso', [
                'fileId' => $fileId
            ]);
            return $deletedFile;
        } catch (\Exception $e) {
            \Log::error('Google Drive API Error - deleteFile', [
                'error' => $e->getMessage(),
                'file_id' => $fileId
            ]);
            throw $e;
        }
    }

    /**
     * Compartilhar arquivo publicamente (link de visualização)
     */
    public function shareFile($fileId, $role = 'reader')
    {
        \Log::info('GoogleDriveService::shareFile chamado', [
            'fileId' => $fileId,
            'role' => $role
        ]);
        try {
            $permission = new \Google_Service_Drive_Permission([
                'type' => 'anyone',
                'role' => $role, // 'reader' ou 'writer'
            ]);
            $this->service->permissions->create($fileId, $permission, [
                'supportsAllDrives' => true,
            ]);
            $file = $this->service->files->get($fileId, [
                'fields' => 'webViewLink',
                'supportsAllDrives' => true,
            ]);
            \Log::info('GoogleDriveService::shareFile sucesso', [
                'fileId' => $fileId,
                'webViewLink' => $file->getWebViewLink()
            ]);
            return $file->getWebViewLink();
        } catch (\Exception $e) {
            \Log::error('Google Drive API Error - shareFile', [
                'error' => $e->getMessage(),
                'file_id' => $fileId
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza um arquivo no Google Drive
     */
    public function updateFile($fileId, $filePath, $fileName = null, $mimeType = null)
    {
        try {
            if (!$mimeType) {
                $mimeType = mime_content_type($filePath);
            }

            $fileMetadata = new DriveFile();

            if ($fileName) {
                $fileMetadata->setName($fileName);
            }

            $content = file_get_contents($filePath);

            $file = $this->service->files->update($fileId, $fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id,name,mimeType,size,createdTime,modifiedTime,parents,webContentLink'
            ]);

            return $file;
        } catch (\Exception $e) {
            Log::error('Google Drive API Error - updateFile', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
                'file_path' => $filePath
            ]);
            throw $e;
        }
    }

    /**
     * Move um arquivo ou pasta para outro diretório
     */
    public function moveFile($fileId, $newParentId)
    {
        try {
            $file = $this->service->files->get($fileId, ['fields' => 'parents']);
            $previousParents = join(',', $file->getParents());

            $file = $this->service->files->update($fileId, new DriveFile(), [
                'addParents' => $newParentId,
                'removeParents' => $previousParents,
                'fields' => 'id,name,mimeType,parents'
            ]);

            return $file;
        } catch (\Exception $e) {
            Log::error('Google Drive API Error - moveFile', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
                'new_parent_id' => $newParentId
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza uma pasta no Google Drive (nome e/ou localização)
     */
    public function updateFolder($folderId, $data)
    {
        try {
            $fileMetadata = new DriveFile();

            // Atualizar nome se fornecido
            if (isset($data['name'])) {
                $fileMetadata->setName($data['name']);
            }

            $updateParams = [
                'fields' => 'id,name,mimeType,parents'
            ];

            // Se há mudança de pasta pai
            if (isset($data['parent_id'])) {
                $currentFile = $this->service->files->get($folderId, ['fields' => 'parents']);
                $previousParents = join(',', $currentFile->getParents());

                if ($data['parent_id']) {
                    // Mover para nova pasta pai
                    $updateParams['addParents'] = $data['parent_id'];
                    $updateParams['removeParents'] = $previousParents;
                } else {
                    // Mover para raiz
                    $updateParams['removeParents'] = $previousParents;
                }
            }

            $folder = $this->service->files->update($folderId, $fileMetadata, $updateParams);

            return $folder;
        } catch (\Exception $e) {
            Log::error('Google Drive API Error - updateFolder', [
                'error' => $e->getMessage(),
                'folder_id' => $folderId,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Gera um link de download para um arquivo
     */
    public function getDownloadLink($fileId)
    {
        try {
            Log::info('GoogleDriveService: Getting download link', ['file_id' => $fileId]);
            
            $file = $this->service->files->get($fileId, [
                'fields' => 'webContentLink,webViewLink,exportLinks,mimeType,name',
                'supportsAllDrives' => true
            ]);
            
            $webContentLink = $file->getWebContentLink();
            $webViewLink = $file->getWebViewLink();
            $exportLinks = $file->getExportLinks();
            $mimeType = $file->getMimeType();
            
            Log::info('GoogleDriveService: File download information', [
                'file_id' => $fileId,
                'file_name' => $file->getName(),
                'mime_type' => $mimeType,
                'web_content_link' => $webContentLink,
                'web_view_link' => $webViewLink,
                'has_export_links' => !empty($exportLinks)
            ]);
            
            // Para arquivos do Google Workspace (Docs, Sheets, etc.), usar exportLinks
            if (!$webContentLink && $exportLinks) {
                Log::info('GoogleDriveService: Using export links for Google Workspace file');
                
                // Prioridade para PDF se disponível
                if (isset($exportLinks['application/pdf'])) {
                    Log::info('GoogleDriveService: Using PDF export link');
                    return $exportLinks['application/pdf'];
                }
                
                // Senão, usar o primeiro link disponível
                $firstExportLink = array_values($exportLinks)[0];
                Log::info('GoogleDriveService: Using first available export link', ['link' => $firstExportLink]);
                return $firstExportLink;
            }
            
            // Para arquivos normais, usar webContentLink
            if ($webContentLink) {
                Log::info('GoogleDriveService: Using web content link');
                return $webContentLink;
            }
            
            // Fallback para webViewLink se não houver webContentLink
            if ($webViewLink) {
                Log::info('GoogleDriveService: Using web view link as fallback');
                return $webViewLink;
            }
            
            throw new \Exception('Nenhum link de download disponível para este arquivo');
            
        } catch (\Exception $e) {
            Log::error('Google Drive API Error - getDownloadLink', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Verifica se um item é uma pasta
     */
    public function isFolder($mimeType)
    {
        return $mimeType === 'application/vnd.google-apps.folder';
    }

    /**
     * Busca o caminho completo de uma pasta
     */
    public function getFolderPath($folderId)
    {
        try {
            $path = [];
            $currentId = $folderId;

            while ($currentId) {
                $folder = $this->getFolder($currentId);
                array_unshift($path, $folder->getName());

                $parents = $folder->getParents();
                $currentId = $parents ? $parents[0] : null;
            }

            return implode(' / ', $path);
        } catch (\Exception $e) {
            Log::error('Google Drive API Error - getFolderPath', [
                'error' => $e->getMessage(),
                'folder_id' => $folderId
            ]);
            throw $e;
        }
    }

    /**
     * Baixa o conteúdo de um arquivo diretamente
     */
    public function downloadFileContent($fileId)
    {
        try {
            Log::info('GoogleDriveService: Downloading file content', ['file_id' => $fileId]);
            
            // Primeiro, obter informações do arquivo
            $file = $this->service->files->get($fileId, [
                'fields' => 'mimeType,name,exportLinks,webContentLink',
                'supportsAllDrives' => true
            ]);
            
            $mimeType = $file->getMimeType();
            $exportLinks = $file->getExportLinks();
            
            Log::info('GoogleDriveService: File info for content download', [
                'file_id' => $fileId,
                'mime_type' => $mimeType,
                'has_export_links' => !empty($exportLinks)
            ]);
            
            // Para arquivos do Google Workspace, usar exportLinks
            if (strpos($mimeType, 'application/vnd.google-apps.') === 0 && $exportLinks) {
                Log::info('GoogleDriveService: Downloading Google Workspace file via export');
                
                // Prioridade para PDF
                if (isset($exportLinks['application/pdf'])) {
                    $exportMimeType = 'application/pdf';
                } else {
                    // Usar o primeiro formato disponível
                    $exportMimeType = array_keys($exportLinks)[0];
                }
                
                Log::info('GoogleDriveService: Using export format', [
                    'export_mime_type' => $exportMimeType
                ]);
                
                // Usar o método export do serviço do Google Drive
                $response = $this->service->files->export($fileId, $exportMimeType, [
                    'supportsAllDrives' => true
                ]);
                
                Log::info('GoogleDriveService: Export download successful');
                return $response->getBody()->getContents();
            } else {
                // Para arquivos normais, usar get com alt=media
                Log::info('GoogleDriveService: Downloading regular file via get with alt=media');
                
                $response = $this->service->files->get($fileId, [
                    'alt' => 'media',
                    'supportsAllDrives' => true
                ]);
                
                Log::info('GoogleDriveService: Regular file download successful');
                return $response->getBody()->getContents();
            }
            
        } catch (\Exception $e) {
            Log::error('Google Drive API Error - downloadFileContent', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Lista Shared Drives disponíveis
     */
    public function listSharedDrives()
    {
        try {
            \Log::emergency('🔍 Listando Shared Drives disponíveis...');
            
            $response = $this->service->drives->listDrives([
                'fields' => 'drives(id,name)',
                'pageSize' => 10
            ]);
            
            $drives = $response->getDrives();
            
            \Log::emergency('📁 Shared Drives encontrados: ' . count($drives));
            foreach ($drives as $drive) {
                \Log::emergency('Drive: ' . $drive->getName() . ' (ID: ' . $drive->getId() . ')');
            }
            
            return $drives;
        } catch (\Exception $e) {
            \Log::emergency('❌ Erro ao listar Shared Drives: ' . $e->getMessage());
            return [];
        }
    }
}
