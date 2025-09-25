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
    private $sharedDriveId; // DESABILITADO - Mantido para compatibilidade
    private $rootFolderId;

    /**
     * IMPORTANTE: Este serviço foi configurado para NÃO usar Shared Drives
     * 
     * Todas as operações (upload, criação de pastas, etc.) são feitas
     * diretamente no Drive Pessoal da Service Account.
     * 
     * Para reativar Shared Drives:
     * 1. Descomentar as linhas relacionadas ao $this->sharedDriveId
     * 2. Adicionar novamente os parâmetros 'supportsAllDrives' => true
     * 3. Configurar GOOGLE_SHARED_DRIVE_ID no .env
     */
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
        
        // 🎯 NOVO: Priorizar OAuth se já estiver autenticado (evita quota da Service Account)
        if ($this->isOAuthAuthenticated()) {
            $this->setupOAuthClient();
            return;
        }
        
        // Caso não tenha OAuth, usar Service Account se disponível
        $serviceAccountFile = config('services.google.service_account_file');
        if ($serviceAccountFile && file_exists($serviceAccountFile)) {
            $this->client->setAuthConfig($serviceAccountFile);
            $this->client->setScopes([GoogleServiceDrive::DRIVE]);
            $this->service = new GoogleServiceDrive($this->client);
            return;
        }
        
        // Último caso: configurar OAuth sem token
        $this->setupOAuthClient();
    }
    
    private function setupOAuthClient()
    {
        // Configuração OAuth básica
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(url(config('services.google.redirect_uri', '/google/callback')));
        $this->client->setScopes([GoogleServiceDrive::DRIVE]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        
        // Verificar se temos access token salvo
        $accessToken = $this->getStoredAccessToken();
        if ($accessToken) {
            try {
                $this->client->setAccessToken($accessToken);
                
                // Renovar se necessário
                if ($this->client->isAccessTokenExpired()) {
                    \Log::info('🔄 Token OAuth expirado, renovando...');
                    $this->refreshAccessToken();
                }
            } catch (\Exception $e) {
                \Log::error('❌ Erro ao configurar token OAuth: ' . $e->getMessage());
                // Se falhar, limpar token e continuar sem autenticação
                $this->clearStoredToken();
            }
        }
        
        $this->service = new GoogleServiceDrive($this->client);
    }
    
    private function getStoredAccessToken()
    {
        $tokenPath = storage_path('app/google_oauth_token.json');
        if (file_exists($tokenPath)) {
            return json_decode(file_get_contents($tokenPath), true);
        }
        return null;
    }
    
    private function refreshAccessToken()
    {
        try {
            \Log::info('🔄 Tentando renovar token OAuth...');
            
            $refreshToken = $this->client->getRefreshToken();
            if (!$refreshToken) {
                // Tentar obter refresh token do arquivo salvo
                $storedToken = $this->getStoredAccessToken();
                if ($storedToken && isset($storedToken['refresh_token'])) {
                    $this->client->setAccessToken($storedToken);
                    $refreshToken = $storedToken['refresh_token'];
                    \Log::info('🔑 Refresh token obtido do arquivo salvo');
                } else {
                    \Log::error('❌ Nenhum refresh token disponível. Reautenticação necessária.');
                    throw new \Exception('Refresh token não encontrado. Usuário precisa reautenticar.');
                }
            }
            
            $newAccessToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
            
            if (isset($newAccessToken['error'])) {
                \Log::error('❌ Erro na resposta do Google: ' . json_encode($newAccessToken));
                throw new \Exception('Erro ao renovar token: ' . $newAccessToken['error_description']);
            }
            
            if (isset($newAccessToken['access_token'])) {
                // Manter o refresh token se não vier um novo
                if (!isset($newAccessToken['refresh_token']) && isset($storedToken['refresh_token'])) {
                    $newAccessToken['refresh_token'] = $storedToken['refresh_token'];
                }
                
                $this->storeAccessToken($newAccessToken);
                $this->client->setAccessToken($newAccessToken);
                \Log::info('✅ Token OAuth renovado com sucesso');
                return true;
            } else {
                \Log::error('❌ Resposta inválida ao renovar token: ' . json_encode($newAccessToken));
                throw new \Exception('Resposta inválida ao renovar token');
            }
        } catch (\Exception $e) {
            \Log::error('❌ Erro ao renovar token OAuth: ' . $e->getMessage());
            // Limpar token inválido
            $this->clearStoredToken();
            throw $e;
        }
    }
    
    private function storeAccessToken($token)
    {
        $tokenPath = storage_path('app/google_oauth_token.json');
        file_put_contents($tokenPath, json_encode($token));
    }

    private function clearStoredToken()
    {
        $tokenPath = storage_path('app/google_oauth_token.json');
        if (file_exists($tokenPath)) {
            unlink($tokenPath);
            \Log::info('🗑️ Token OAuth inválido removido');
        }
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
            
            // Se for erro de token expirado/inválido, tentar renovar
            if (strpos($e->getMessage(), 'invalid_grant') !== false || 
                strpos($e->getMessage(), 'Token has been expired') !== false) {
                \Log::warning('🔄 Token expirado detectado em listFiles, tentando renovar...');
                try {
                    $this->refreshAccessToken();
                    // Tentar novamente após renovação
                    return $this->listFiles($folderId, $fields, $customQuery);
                } catch (\Exception $refreshError) {
                    \Log::error('❌ Falha ao renovar token: ' . $refreshError->getMessage());
                    throw new \Exception('Token expirado. Por favor, faça login novamente.');
                }
            }
            
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
     * Cria uma pasta no Google Drive
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
                // Removido: 'supportsAllDrives' => true,
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
     * Cria uma estrutura de pastas recursivamente
     */
    public function createFolderStructure($folderPath, $parentId = null)
    {
        $parentId = $parentId ?? $this->rootFolderId;
        $pathParts = explode('/', trim($folderPath, '/'));
        $currentParentId = $parentId;

        foreach ($pathParts as $folderName) {
            if (empty($folderName)) continue;
            
            try {
                // Verificar se a pasta já existe
                $existingFolder = $this->findFolderByName($folderName, $currentParentId);
                if ($existingFolder) {
                    $currentParentId = $existingFolder->getId();
                } else {
                    // Criar nova pasta
                    $newFolder = $this->createFolder($folderName, $currentParentId);
                    $currentParentId = $newFolder->getId();
                }
            } catch (\Exception $e) {
                \Log::error('Erro ao criar estrutura de pastas', [
                    'folderName' => $folderName,
                    'parentId' => $currentParentId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        return $currentParentId;
    }

    /**
     * Encontra uma pasta pelo nome dentro de um diretório pai
     */
    public function findFolderByName($folderName, $parentId = null)
    {
        try {
            // Escapar caracteres especiais no nome da pasta
            $escapedName = str_replace("'", "\\'", $folderName);
            
            $query = "mimeType='application/vnd.google-apps.folder' and name='{$escapedName}'";
            if ($parentId) {
                $query .= " and '{$parentId}' in parents";
            }
            
            $results = $this->service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id,name,mimeType,parents)',
                'pageSize' => 1
            ]);

            $files = $results->getFiles();
            return !empty($files) ? $files[0] : null;
        } catch (\Exception $e) {
            \Log::error('Erro ao buscar pasta por nome', [
                'folderName' => $folderName,
                'parentId' => $parentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Faz upload de uma pasta com subpastas e arquivos
     */
    public function uploadFolder($folderPath, $parentId = null)
    {
        $parentId = $parentId ?? $this->rootFolderId;
        $results = [
            'folders_created' => 0,
            'files_uploaded' => 0,
            'errors' => [],
            'root_folder_id' => null,
            'root_folder_name' => null,
            'subfolders_created' => [], // Array para armazenar subpastas criadas
        ];

        \Log::info('uploadFolder iniciado', [
            'folderPath' => $folderPath,
            'parentId' => $parentId
        ]);

        try {
            // Capturar nome da pasta raiz
            $rootFolderName = basename($folderPath);
            // Criar/obter pasta raiz no Google Drive
            $googleRootFolder = $this->findFolderByName($rootFolderName, $parentId);
            if (!$googleRootFolder) {
                $googleRootFolder = $this->createFolder($rootFolderName, $parentId);
            }
            $results['root_folder_id'] = $googleRootFolder->getId();
            $results['root_folder_name'] = $googleRootFolder->getName();

            // Processar recursivamente a partir da pasta raiz
            \Log::info('🚀 Iniciando processamento recursivo', [
                'folderPath' => $folderPath,
                'rootFolderId' => $googleRootFolder->getId(),
                'rootFolderName' => $googleRootFolder->getName()
            ]);
            $this->processFolderRecursively($folderPath, $googleRootFolder->getId(), $results);

            \Log::info('uploadFolder concluído', [
                'results' => $results,
                'folders_created' => $results['folders_created'],
                'files_uploaded' => $results['files_uploaded'],
                'subfolders_created_count' => count($results['subfolders_created']),
                'errors_count' => count($results['errors'])
            ]);

            return $results;
        } catch (\Exception $e) {
            $results['errors'][] = 'Erro geral: ' . $e->getMessage();
            \Log::error('Erro geral no uploadFolder', [
                'error' => $e->getMessage(),
                'folderPath' => $folderPath,
                'parentId' => $parentId
            ]);
            return $results;
        }
    }

    /**
     * Processa uma pasta recursivamente, criando subpastas e fazendo upload de arquivos
     */
    private function processFolderRecursively($folderPath, $parentId, &$results)
    {
        \Log::info('processFolderRecursively iniciado', [
            'folderPath' => $folderPath,
            'parentId' => $parentId
        ]);
        
        if (!is_dir($folderPath)) {
            $results['errors'][] = "Pasta não encontrada: {$folderPath}";
            \Log::error('Pasta não encontrada', ['folderPath' => $folderPath]);
            return;
        }

        $folderName = basename($folderPath);
        \Log::info('Processando pasta', [
            'folderName' => $folderName,
            'folderPath' => $folderPath,
            'parentId' => $parentId
        ]);
        
        // Criar pasta no Google Drive
        try {
            \Log::info('Buscando pasta existente', ['folderName' => $folderName, 'parentId' => $parentId]);
            $googleFolder = $this->findFolderByName($folderName, $parentId);
            
            if (!$googleFolder) {
                \Log::info('Pasta não encontrada, criando nova', ['folderName' => $folderName, 'parentId' => $parentId]);
                $googleFolder = $this->createFolder($folderName, $parentId);
                $results['folders_created']++;
                $results['subfolders_created'][] = [
                    'id' => $googleFolder->getId(),
                    'name' => $googleFolder->getName()
                ];
                \Log::info('Pasta criada com sucesso', [
                    'folderName' => $folderName,
                    'folderId' => $googleFolder->getId(),
                    'folders_created' => $results['folders_created']
                ]);
            } else {
                \Log::info('Pasta encontrada, mas contando como processada', [
                    'folderName' => $folderName,
                    'folderId' => $googleFolder->getId()
                ]);
                // Contar como pasta processada mesmo que já exista
                $results['folders_created']++;
            }
            
            $googleFolderId = $googleFolder->getId();
        } catch (\Exception $e) {
            $results['errors'][] = "Erro ao criar pasta '{$folderName}': " . $e->getMessage();
            \Log::error('Erro ao criar pasta', [
                'folderName' => $folderName,
                'parentId' => $parentId,
                'error' => $e->getMessage()
            ]);
            return;
        }

        // Processar conteúdo da pasta
        $items = scandir($folderPath);
        \Log::info('Itens encontrados na pasta', [
            'folderPath' => $folderPath,
            'items' => $items,
            'total_items' => count($items)
        ]);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $itemPath = $folderPath . DIRECTORY_SEPARATOR . $item;
            \Log::info('Processando item', [
                'item' => $item,
                'itemPath' => $itemPath,
                'is_dir' => is_dir($itemPath)
            ]);

            if (is_dir($itemPath)) {
                // Processar subpasta recursivamente
                \Log::info('Processando subpasta', [
                    'subfolder' => $item,
                    'parentFolderId' => $googleFolderId
                ]);
                $this->processFolderRecursively($itemPath, $googleFolderId, $results);
            } else {
                // Fazer upload do arquivo
                \Log::info('Fazendo upload de arquivo', [
                    'fileName' => $item,
                    'filePath' => $itemPath,
                    'parentFolderId' => $googleFolderId
                ]);
                
                try {
                    $fileSize = filesize($itemPath);
                    \Log::info('Informações do arquivo', [
                        'fileName' => $item,
                        'fileSize' => $fileSize
                    ]);
                    
                    // Detectar MIME type de forma compatível
                    $mimeType = null;
                    if (function_exists('mime_content_type')) {
                        $mimeType = mime_content_type($itemPath);
                    } else {
                        // Fallback para Windows
                        $extension = strtolower(pathinfo($itemPath, PATHINFO_EXTENSION));
                        $mimeTypes = [
                            'pdf' => 'application/pdf',
                            'doc' => 'application/msword',
                            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'xls' => 'application/vnd.ms-excel',
                            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'ppt' => 'application/vnd.ms-powerpoint',
                            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'txt' => 'text/plain',
                            'jpg' => 'image/jpeg',
                            'jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif',
                            'zip' => 'application/zip',
                            'rar' => 'application/x-rar-compressed',
                        ];
                        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
                    }
                    
                    \Log::info('MIME type detectado', [
                        'fileName' => $item,
                        'mimeType' => $mimeType
                    ]);
                    
                    if ($fileSize > 5 * 1024 * 1024) {
                        \Log::info('Upload de arquivo grande', ['fileName' => $item, 'size' => $fileSize]);
                        $uploadedFile = $this->uploadLargeFile($itemPath, $item, $googleFolderId, $mimeType);
                    } else {
                        \Log::info('Upload de arquivo normal', ['fileName' => $item, 'size' => $fileSize]);
                        $uploadedFile = $this->uploadFile($itemPath, $item, $googleFolderId, $mimeType);
                    }
                    
                    $results['files_uploaded']++;
                    \Log::info('Arquivo enviado com sucesso', [
                        'fileName' => $item,
                        'files_uploaded' => $results['files_uploaded']
                    ]);
                } catch (\Exception $e) {
                    $results['errors'][] = "Erro ao fazer upload de '{$item}': " . $e->getMessage();
                    \Log::error('Erro ao fazer upload de arquivo', [
                        'fileName' => $item,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Faz upload de um arquivo para o Google Drive
     */
    public function uploadFile($filePath, $fileName, $parentId = null, $mimeType = null)
    {
        $parentId = $parentId ?? $this->rootFolderId;
        
        try {
            return $this->attemptUpload($filePath, $fileName, $parentId, $mimeType);
        } catch (\Exception $e) {
            // Se falhar com Service Account por quota, tentar OAuth
            if (strpos($e->getMessage(), 'quota') !== false || 
                strpos($e->getMessage(), 'storage') !== false ||
                strpos($e->getMessage(), 'limit') !== false) {
                
                if ($this->isOAuthAuthenticated()) {
                    return $this->attemptUpload($filePath, $fileName, $parentId, $mimeType, true);
                } else {
                    throw new \Exception('Erro de quota do Google Drive. Configure OAuth em /google-setup.');
                }
            }
            
            throw $e;
        }
    }
    
    private function attemptUpload($filePath, $fileName, $parentId, $mimeType, $forceOAuth = false)
    {
        // Se forçar OAuth ou estiver autenticado via OAuth
        if ($forceOAuth && $this->isOAuthAuthenticated()) {
            $this->initializeOAuthService();
        }
        
        if (!$mimeType) {
            $mimeType = mime_content_type($filePath);
        }

        $fileMetadata = new DriveFile([
            'name' => $fileName,
            'parents' => [$parentId]
        ]);

        $content = file_get_contents($filePath);

        $optParams = [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id,name,mimeType,size,createdTime,modifiedTime,parents,webContentLink'
        ];

        $file = $this->service->files->create($fileMetadata, $optParams);

        return $file;
    }
    
    private function initializeOAuthService()
    {
        $this->setupOAuthClient();
        $this->service = new GoogleServiceDrive($this->client);
    }
    
    private function isOAuthAuthenticated()
    {
        $tokenPath = storage_path('app/google_oauth_token.json');
        return file_exists($tokenPath);
    }

    /**
     * Verifica se o usuário está autenticado e o token é válido
     */
    public function isValidAuthentication()
    {
        try {
            // Testar uma chamada simples para verificar se o serviço funciona
            // Funciona tanto com Service Account quanto com OAuth
            $this->service->about->get(['fields' => 'user']);
            return true;
        } catch (\Exception $e) {
            \Log::warning('Falha na autenticação Google Drive: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Força uma nova autenticação removendo tokens existentes
     */
    public function forceReauth()
    {
        $this->clearStoredToken();
        $this->setupOAuthClient();
        return url('google/auth');
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
                // Removido: 'supportsAllDrives' => true,
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
                // Removido: 'supportsAllDrives' => true,
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
                // Removido: 'supportsAllDrives' => true,
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
                // Removido: 'supportsAllDrives' => true,
            ]);
            $file = $this->service->files->get($fileId, [
                'fields' => 'webViewLink',
                // Removido: 'supportsAllDrives' => true,
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
            $this->initializeClient();
            
            $response = $this->service->files->get($fileId, [
                'alt' => 'media'
            ]);
            
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            \Log::error('Google Drive API Error - downloadFileContent', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
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
    
    /**
     * Verificar se o cliente está autenticado
     */
    public function isAuthenticated()
    {
        $accessToken = $this->getStoredAccessToken();
        if (!$accessToken) {
            return false;
        }
        
        $this->client->setAccessToken($accessToken);
        return !$this->client->isAccessTokenExpired();
    }
    
    /**
     * Obter URL de autorização OAuth
     */
    public function getAuthUrl()
    {
        // Garantir que estamos usando OAuth, não Service Account
        $this->setupOAuthClient();
        return $this->client->createAuthUrl();
    }
    
    /**
     * Processar código de autorização OAuth
     */
    public function handleAuthCallback($code)
    {
        try {
            // Garantir que estamos usando OAuth, não Service Account
            $this->setupOAuthClient();
            
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($accessToken['error'])) {
                throw new \Exception('Erro OAuth: ' . $accessToken['error']);
            }
            
            $this->storeAccessToken($accessToken);
            $this->client->setAccessToken($accessToken);
            
            \Log::emergency('✅ OAuth configurado com sucesso!');
            return true;
        } catch (\Exception $e) {
            \Log::emergency('❌ Erro ao processar OAuth: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete forçado sem verificação de permissões (para contornar 403)
     */
    public function forceDeleteFile($fileId)
    {
        \Log::emergency('🗑️ FORCE DELETE - EXCLUSÃO PERMANENTE', [
            'fileId' => $fileId,
            'auth_type' => $this->isOAuthAuthenticated() ? 'OAuth' : 'Service Account'
        ]);
        
        try {
            // Tentar com OAuth primeiro se disponível
            if ($this->isOAuthAuthenticated()) {
                \Log::emergency('🔐 Usando OAuth para delete permanente');
                $this->initializeOAuthService();
            }
            
            // Fazer exclusão permanente direta
            \Log::emergency('🚨 Executando delete permanente...');
            $this->service->files->delete($fileId);
            
            \Log::emergency('✅ Exclusão permanente realizada com sucesso!', [
                'fileId' => $fileId
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::emergency('❌ Erro na exclusão permanente: ' . $e->getMessage());
            
            // Se der erro, tentar mover para lixeira como fallback
            try {
                \Log::emergency('🔄 Tentando mover para lixeira como fallback...');
                $deletedFile = $this->service->files->update($fileId, new DriveFile(['trashed' => true]), [
                    'fields' => 'id,trashed,name'
                ]);
                
                \Log::emergency('✅ Arquivo movido para lixeira como fallback', [
                    'fileId' => $fileId,
                    'fileName' => $deletedFile->getName(),
                    'trashed' => $deletedFile->getTrashed()
                ]);
                
                return $deletedFile;
            } catch (\Exception $e2) {
                \Log::emergency('❌ Ambos os métodos falharam: ' . $e2->getMessage());
                throw new \Exception('Não foi possível deletar o arquivo. Erro: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Upload de arquivo grande com progress e chunks
     */
    public function uploadLargeFile($filePath, $fileName, $parentId = null, $mimeType = null)
    {
        $parentId = $parentId ?? $this->rootFolderId;
        $fileSize = filesize($filePath);
        
        \Log::emergency('📤 UPLOAD ARQUIVO GRANDE', [
            'fileName' => $fileName,
            'fileSize' => $fileSize,
            'fileSizeMB' => round($fileSize / 1024 / 1024, 2) . 'MB',
            'parentId' => $parentId,
            'authType' => $this->isOAuthAuthenticated() ? 'OAuth' : 'Service Account'
        ]);
        
        // Se arquivo > 5MB, usar upload resumable
        if ($fileSize > 5 * 1024 * 1024) {
            return $this->uploadResumable($filePath, $fileName, $parentId, $mimeType);
        }
        
        // Senão, upload normal
        return $this->uploadFile($filePath, $fileName, $parentId, $mimeType);
    }
    
    private function uploadResumable($filePath, $fileName, $parentId, $mimeType)
    {
        try {
            // Garantir OAuth para arquivos grandes
            if ($this->isOAuthAuthenticated()) {
                $this->initializeOAuthService();
            }
            
            if (!$mimeType) {
                $mimeType = mime_content_type($filePath);
            }

            $fileMetadata = new DriveFile([
                'name' => $fileName,
                'parents' => [$parentId]
            ]);

            // Configurar upload resumable
            $chunkSizeBytes = 1 * 1024 * 1024; // 1MB chunks
            $this->client->setDefer(true);

            $request = $this->service->files->create($fileMetadata);
            $media = new \Google_Http_MediaFileUpload(
                $this->client,
                $request,
                $mimeType,
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(filesize($filePath));

            // Upload por chunks
            $status = false;
            $handle = fopen($filePath, "rb");
            
            \Log::emergency('🔄 Iniciando upload resumable por chunks');
            
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
            }
            
            fclose($handle);
            $this->client->setDefer(false);

            if ($status) {
                \Log::emergency('✅ Upload resumable concluído!', [
                    'fileId' => $status->getId(),
                    'fileName' => $status->getName()
                ]);
                return $status;
            }
            
            throw new \Exception('Upload resumable falhou');
            
        } catch (\Exception $e) {
            \Log::emergency('❌ Erro no upload resumable: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Verifica se um arquivo/pasta existe no Google Drive
     */
    public function fileExists($fileId)
    {
        try {
            $this->initializeClient();
            $this->service->files->get($fileId, [
                'fields' => 'id'
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Buscar arquivos no Google Drive usando query
     */
    public function searchFiles($query, $fields = 'files(id,name,mimeType,parents)')
    {
        try {
            $this->initializeClient();
            
            $optParams = [
                'q' => $query,
                'fields' => $fields,
                'pageSize' => 100
            ];
            
            $results = $this->service->files->listFiles($optParams);
            return $results->getFiles();
            
        } catch (\Exception $e) {
            \Log::error('Google Drive API Error - searchFiles', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
