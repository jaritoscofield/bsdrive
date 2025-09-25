<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleDriveFolderController extends Controller
{
    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    // Lista pastas do Google Drive (raiz ou por parentId)
    public function index(Request $request)
    {
        $user = auth()->user();
        $parentId = $request->get('parent_id');
        
        // Se não foi especificado parent_id, listar todas as pastas que o usuário tem acesso
        if (!$parentId) {
            $accessibleFolderIds = $user->getAccessibleFolderIds();
            Log::info("GoogleDriveFolderController::index - Sem parent_id, listando pastas acessíveis", [
                'user_id' => $user->id,
                'count' => count($accessibleFolderIds)
            ]);
            $folders = [];
            try {
                foreach ($accessibleFolderIds as $fid) {
                    try {
                        $folder = $this->googleDriveService->getFolder($fid);
                        if ($folder) {
                            $folders[] = [
                                'id' => $folder->getId(),
                                'name' => $folder->getName(),
                                'mimeType' => $folder->getMimeType(),
                                'parents' => $folder->getParents() ?: [],
                            ];
                        }
                    } catch (\Exception $e) {
                        Log::debug('Falha ao obter pasta acessível', ['folder_id' => $fid, 'error' => $e->getMessage()]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Erro ao listar pastas acessíveis', ['error' => $e->getMessage()]);
            }

            // Renderiza visão de índice com lista de pastas acessíveis (sem pasta pai)
            $parentFolder = null;
            return view('google-drive.folders.index', compact('folders', 'parentFolder', 'parentId'));
        }
        
        // Verificar se o usuário pode acessar a pasta parent
        $canAccess = $this->canUserAccessFolder($user, $parentId);
        Log::info("GoogleDriveFolderController::index - Verificação de acesso", [
            'user_id' => $user->id,
            'folder_id' => $parentId,
            'can_access' => $canAccess,
            'user_role' => $user->role
        ]);
        
        if (!$canAccess) {
            Log::error("GoogleDriveFolderController::index - Acesso negado", [
                'user_id' => $user->id,
                'folder_id' => $parentId,
                'personal_folder_id' => $user->getPersonalFolderId(),
                'accessible_folders' => $user->getAccessibleFolderIds()
            ]);
            
            // Se o acesso foi negado e não há parent_id na requisição original, 
            // tentar redirecionar para a pasta pessoal
            if (!$request->get('parent_id')) {
                $personalFolderId = $user->getPersonalFolderId();
                if ($personalFolderId && $personalFolderId !== $parentId) {
                    return redirect()->route('folders.index', ['parent_id' => $personalFolderId]);
                }
            }
            
            abort(403, 'Você não tem permissão para acessar esta pasta.');
        }
        
        // Buscar apenas as subpastas da pasta atual (parentId)
        $folders = $this->googleDriveService->listFiles($parentId, 'files(id,name,mimeType,parents,createdTime,modifiedTime)');
        
        Log::info("GoogleDriveFolderController::index - Itens retornados do Google Drive", [
            'parent_id' => $parentId,
            'total_items' => count($folders),
            'items' => array_map(function($item) {
                return [
                    'id' => $item['id'] ?? 'N/A',
                    'name' => $item['name'] ?? 'N/A',
                    'mimeType' => $item['mimeType'] ?? 'N/A',
                    'createdTime' => $item['createdTime'] ?? 'N/A'
                ];
            }, $folders)
        ]);
        
        // Filtrar apenas pastas (não arquivos)
        $folders = array_filter($folders, function($file) {
            return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
        });
        $folders = array_values($folders);
        
        Log::info("GoogleDriveFolderController::index - Pastas filtradas", [
            'parent_id' => $parentId,
            'folders_count' => count($folders),
            'folders' => array_map(function($folder) {
                return [
                    'id' => $folder['id'] ?? 'N/A',
                    'name' => $folder['name'] ?? 'N/A',
                    'createdTime' => $folder['createdTime'] ?? 'N/A'
                ];
            }, $folders)
        ]);
        
        // Verificar se há pastas que podem ter sido criadas recentemente mas não aparecem na listagem
        // Isso é um problema conhecido do Google Drive API
        // DESABILITADO: Causa erros 404 ao tentar acessar pastas que não existem mais
        /*
        $recentlyCreatedFolders = $this->findRecentlyCreatedFolders($parentId);
        if (!empty($recentlyCreatedFolders)) {
            Log::info("GoogleDriveFolderController::index - Pastas criadas recentemente encontradas", [
                'parent_id' => $parentId,
                'recent_folders' => $recentlyCreatedFolders
            ]);
            
            // Adicionar apenas as pastas recentes que não estão na lista original
            $existingFolderIds = array_column($folders, 'id');
            foreach ($recentlyCreatedFolders as $recentFolder) {
                if (!in_array($recentFolder['id'], $existingFolderIds)) {
                    // Verificar se a pasta ainda existe no Google Drive antes de adicionar
                    try {
                        $folderExists = $this->googleDriveService->fileExists($recentFolder['id']);
                        if ($folderExists) {
                            $folders[] = $recentFolder;
                            Log::info("Pasta recente adicionada à lista", [
                                'folder_id' => $recentFolder['id'],
                                'folder_name' => $recentFolder['name']
                            ]);
                        } else {
                            Log::info("Pasta recente não existe mais no Google Drive, não adicionando", [
                                'folder_id' => $recentFolder['id'],
                                'folder_name' => $recentFolder['name']
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::info("Erro ao verificar existência da pasta, não adicionando", [
                            'folder_id' => $recentFolder['id'],
                            'folder_name' => $recentFolder['name'],
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    Log::info("Pasta já existe na lista, não adicionando duplicata", [
                        'folder_id' => $recentFolder['id'],
                        'folder_name' => $recentFolder['name']
                    ]);
                }
            }
        }
        */
        
        // Buscar informações da pasta pai para exibir no breadcrumb
        $parentFolder = null;
        if ($parentId) {
            try {
                $parentFolder = $this->googleDriveService->getFolder($parentId);
            } catch (\Exception $e) {
                Log::warning("Erro ao buscar informações da pasta pai", [
                    'parent_id' => $parentId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return view('google-drive.folders.index', compact('folders', 'parentFolder', 'parentId'));
    }

    // Exibe detalhes de uma pasta do Google Drive
    public function show($id)
    {
        $user = auth()->user();
        
        Log::info("GoogleDriveFolderController::show chamado", [
            'user_id' => $user->id,
            'folder_id' => $id
        ]);
        
        // Verificar se o usuário pode acessar a pasta
        if (!$this->canUserAccessFolder($user, $id)) {
            abort(403, 'Você não tem permissão para acessar esta pasta.');
        }
        
        $folder = $this->googleDriveService->getFolder($id);
        
        // Buscar subpastas
        $subfolders = $this->googleDriveService->listFiles($id, 'files(id,name,mimeType,parents,createdTime,modifiedTime)');
        
        Log::info("GoogleDriveFolderController::show - Subpastas retornadas do Google Drive", [
            'folder_id' => $id,
            'total_subfolders' => count($subfolders),
            'subfolders' => array_map(function($item) {
                return [
                    'id' => $item['id'] ?? 'N/A',
                    'name' => $item['name'] ?? 'N/A',
                    'mimeType' => $item['mimeType'] ?? 'N/A',
                    'createdTime' => $item['createdTime'] ?? 'N/A'
                ];
            }, $subfolders)
        ]);
        
        $subfolders = array_filter($subfolders, function($file) {
            return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
        });
        $subfolders = array_values($subfolders);
        
        Log::info("GoogleDriveFolderController::show - Subpastas filtradas", [
            'folder_id' => $id,
            'subfolders_count' => count($subfolders),
            'subfolders' => array_map(function($folder) {
                return [
                    'id' => $folder['id'] ?? 'N/A',
                    'name' => $folder['name'] ?? 'N/A',
                    'createdTime' => $folder['createdTime'] ?? 'N/A'
                ];
            }, $subfolders)
        ]);
        
        // Verificar se há subpastas que podem ter sido criadas recentemente mas não aparecem na listagem
        // DESABILITADO: Causa erros 404 ao tentar acessar pastas que não existem mais
        /*
        $recentlyCreatedSubfolders = $this->findRecentlyCreatedSubfolders($id);
        if (!empty($recentlyCreatedSubfolders)) {
            Log::info("GoogleDriveFolderController::show - Subpastas criadas recentemente encontradas", [
                'folder_id' => $id,
                'recent_subfolders' => $recentlyCreatedSubfolders
            ]);
            
            // Adicionar apenas as subpastas recentes que não estão na lista original
            $existingSubfolderIds = array_column($subfolders, 'id');
            foreach ($recentlyCreatedSubfolders as $recentSubfolder) {
                if (!in_array($recentSubfolder['id'], $existingSubfolderIds)) {
                    // Verificar se a subpasta ainda existe no Google Drive antes de adicionar
                    try {
                        $subfolderExists = $this->googleDriveService->fileExists($recentSubfolder['id']);
                        if ($subfolderExists) {
                            $subfolders[] = $recentSubfolder;
                            Log::info("Subpasta recente adicionada à lista", [
                                'subfolder_id' => $recentSubfolder['id'],
                                'subfolder_name' => $recentSubfolder['name']
                            ]);
                        } else {
                            Log::info("Subpasta recente não existe mais no Google Drive, não adicionando", [
                                'subfolder_id' => $recentSubfolder['id'],
                                'subfolder_name' => $recentSubfolder['name']
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::info("Erro ao verificar existência da subpasta, não adicionando", [
                            'subfolder_id' => $recentSubfolder['id'],
                            'subfolder_name' => $recentSubfolder['name'],
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    Log::info("Subpasta já existe na lista, não adicionando duplicata", [
                        'subfolder_id' => $recentSubfolder['id'],
                        'subfolder_name' => $recentSubfolder['name']
                    ]);
                }
            }
        }
        */
        
        // Buscar arquivos
        $files = $this->googleDriveService->listFiles($id, 'files(id,name,mimeType,parents,size,createdTime,modifiedTime)');
        $files = array_filter($files, function($file) {
            return isset($file['mimeType']) && $file['mimeType'] !== 'application/vnd.google-apps.folder';
        });
        $files = array_values($files);
        
        Log::info("GoogleDriveFolderController::show - Resultado final", [
            'folder_id' => $id,
            'subfolders_count' => count($subfolders),
            'files_count' => count($files)
        ]);
        
        return view('google-drive.folders.show', compact('folder', 'subfolders', 'files'));
    }

    // Exibe formulário de criação de pasta
    public function create(Request $request)
    {
        $user = auth()->user();
        $parentId = $request->get('parent_id');
        if ($user->role === 'admin_sistema') {
            $sectors = \App\Models\Sector::all();
        } else {
            $sectors = \App\Models\Sector::where('company_id', $user->company_id)->get();
        }
        return view('google-drive.folders.create', compact('parentId', 'sectors'));
    }

    // Cria uma nova pasta no Google Drive
    public function store(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|string',
        ]);
        
        $parentId = $request->parent_id;
        if (empty($parentId) || $parentId === '.' || $parentId === '') {
            $parentId = null;
        }
        
        // Se não foi especificado parent_id, usar a pasta pessoal do usuário
        if (!$parentId) {
            $parentId = $user->getOrCreatePersonalFolder();
        }
        
        // Verificar se o parent_id existe no Google Drive
        if ($parentId && !$this->googleDriveService->fileExists($parentId)) {
            return back()->withErrors(['parent_id' => 'A pasta pai especificada não existe mais no Google Drive.']);
        }
        
        // Verificar se o usuário pode acessar a pasta parent
        if (!$this->canUserAccessFolder($user, $parentId)) {
            return back()->withErrors(['parent_id' => 'Você não tem permissão para criar pastas neste local.']);
        }
        
        $folder = $this->googleDriveService->createFolder($request->name, $parentId);
        // Salvar no banco
        $sectorId = $request->input('sector_id');
        $sector = \App\Models\Sector::find($sectorId);
        if (!$sector) {
            return back()->withErrors(['sector_id' => 'Setor inválido.'])->withInput();
        }
        $folderModel = new \App\Models\Folder();
        $folderModel->name = $folder['name'] ?? $request->name;
        $folderModel->google_drive_id = $folder['id'];
        $folderModel->parent_id = $parentId;
        $folderModel->company_id = $user->company_id;
        $folderModel->sector_id = $sector->id;
        $folderModel->path = $folder['name'] ?? $request->name;
        $folderModel->active = true;
        $folderModel->save();

        return redirect()->route('folders.show', $folder['id'])
            ->with('success', 'Pasta criada no Google Drive e salva no banco! O listener automático irá vinculá-la às empresas.');
    }

    // Exibe formulário de edição de pasta
    public function edit($id)
    {
        $folder = $this->googleDriveService->getFolder($id);
        return view('google-drive.folders.edit', compact('folder'));
    }

    // Atualiza o nome da pasta no Google Drive
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $this->googleDriveService->renameFile($id, $request->name);
        return redirect()->route('folders.show', $id)
            ->with('success', 'Pasta atualizada no Google Drive com sucesso!');
    }

    // Remove uma pasta do Google Drive
    public function destroy($id)
    {
        $user = auth()->user();
        
        // Verificar se o usuário pode acessar a pasta
        if (!$this->canUserAccessFolder($user, $id)) {
            abort(403, 'Você não tem permissão para excluir esta pasta.');
        }
        
        try {
            // Obter informações da pasta antes de deletá-la
            $folder = $this->googleDriveService->getFolder($id);
            $parentId = null;
            
            if (isset($folder['parents']) && !empty($folder['parents'])) {
                $parentId = $folder['parents'][0];
            }
            
            // Excluir pasta do Google Drive usando force delete
            $deleted = $this->googleDriveService->forceDeleteFile($id);
            
            if ($deleted) {
                // Redirecionar para a pasta pai se existir, senão para a lista de pastas
                if ($parentId) {
                    return redirect()->route('folders.show', $parentId)
                        ->with('success', 'Subpasta removida do Google Drive com sucesso!');
                } else {
                    return redirect()->route('folders.index')
                        ->with('success', 'Pasta removida do Google Drive com sucesso!');
                }
            } else {
                return back()->with('error', 'Erro ao remover pasta do Google Drive.');
            }
        } catch (\Exception $e) {
            Log::error('Erro ao excluir pasta do Google Drive', [
                'folder_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Erro ao excluir pasta do Google Drive: ' . $e->getMessage());
        }
    }

    /**
     * Verifica se o usuário pode acessar uma pasta específica
     */
    private function canUserAccessFolder($user, $folderId)
    {
        Log::info("canUserAccessFolder chamado", [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'folder_id' => $folderId,
            'personal_folder_id' => $user->getPersonalFolderId()
        ]);
        
        // Admin do sistema pode acessar tudo
        if ($user->role === 'admin_sistema') {
            Log::info("Acesso liberado: admin_sistema");
            return true;
        }
        
        // Verifica se é a pasta pessoal do usuário
        $personalFolderId = $user->getPersonalFolderId();
        if ($personalFolderId && $personalFolderId === $folderId) {
            Log::info("Acesso liberado: pasta pessoal");
            return true;
        }
        
        // Verifica se a pasta está dentro da área pessoal do usuário
        if ($personalFolderId && $this->isFolderInsidePersonalArea($folderId, $personalFolderId)) {
            Log::info("Acesso liberado: pasta dentro da área pessoal");
            return true;
        }
        
        // Verifica se o usuário tem permissão específica para esta pasta
        $accessibleFolders = $user->getAccessibleFolderIds();
        $hasAccess = in_array($folderId, $accessibleFolders);
        
        // Herança: se alguma pasta acessível for ancestral desta pasta, concede acesso
        if (!$hasAccess && !empty($accessibleFolders)) {
            try {
                if ($this->isFolderDescendantOfAny($folderId, $accessibleFolders)) {
                    Log::info("Acesso liberado por herança de pastas", [
                        'folder_id' => $folderId
                    ]);
                    return true;
                }
            } catch (\Exception $e) {
                Log::warning("Erro ao verificar herança de pastas", [
                    'folder_id' => $folderId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info("Verificação final", [
            'accessible_folders' => $accessibleFolders,
            'folder_in_list' => $hasAccess
        ]);
        
        return $hasAccess;
    }

    // Verifica se a pasta informada é descendente de qualquer uma das pastas fornecidas
    private function isFolderDescendantOfAny(string $folderId, array $ancestorIds): bool
    {
        return $this->isFolderDescendantRecursive($folderId, $ancestorIds, 0);
    }

    private function isFolderDescendantRecursive(string $folderId, array $ancestorIds, int $depth): bool
    {
        if ($depth >= 10) {
            return false;
        }
        $folder = $this->googleDriveService->getFolder($folderId);
        if (!isset($folder['parents']) || empty($folder['parents'])) {
            return false;
        }
        foreach ($folder['parents'] as $parentId) {
            if (in_array($parentId, $ancestorIds, true)) {
                return true;
            }
            if ($this->isFolderDescendantRecursive($parentId, $ancestorIds, $depth + 1)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Verifica se uma pasta está dentro da área pessoal do usuário
     */
    private function isFolderInsidePersonalArea($folderId, $personalFolderId)
    {
        try {
            // Se é a própria pasta pessoal, retorna true
            if ($folderId === $personalFolderId) {
                return true;
            }
            
            $folder = $this->googleDriveService->getFolder($folderId);
            
            // Se não tem parents, não está dentro da área pessoal
            if (!isset($folder['parents']) || empty($folder['parents'])) {
                return false;
            }
            
            // Verifica cada parent até encontrar a pasta pessoal ou chegar na raiz
            foreach ($folder['parents'] as $parentId) {
                if ($parentId === $personalFolderId) {
                    return true;
                }
                
                // Recursivamente verifica os parents (máximo 10 níveis para evitar loop infinito)
                static $depth = 0;
                if ($depth < 10) {
                    $depth++;
                    $result = $this->isFolderInsidePersonalArea($parentId, $personalFolderId);
                    $depth--;
                    if ($result) {
                        return true;
                    }
                }
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("Erro ao verificar se pasta está dentro da área pessoal", [
                'folder_id' => $folderId,
                'personal_folder_id' => $personalFolderId,
                'error' => $e->getMessage()
            ]);
            // Em caso de erro, permite acesso se for admin ou nega se for usuário comum
            $user = auth()->user();
            return $user && $user->role === 'admin_sistema';
        }
    }
    
    /**
     * Busca pastas que podem ter sido criadas recentemente mas não aparecem na listagem
     * Isso resolve um problema conhecido do Google Drive API
     */
    private function findRecentlyCreatedFolders($parentId)
    {
        $recentFolders = [];
        
        try {
            // Lista de IDs de pastas que sabemos que foram criadas recentemente
            // Esta lista pode ser expandida conforme necessário
            $knownRecentFolderIds = [
                '1ehYNN2MxJqxtk5M7kC2ZKnlwgMSS8LJb', // pasta
                '156_HkhSr9JNChYhRHUMF_L70JmlxP6ak', // subpasta
                '1shb4J0KdntAYmEHNH8LH7IVr4MTudfRn', // subpastadasubpasta
                '1ijfLta5xSAY2I2wBr8fbDy_dez40Nf9Y', // pasta01
                '1aHZoBr4SYeoDirvPdEgUf0-aAZ6ACqTe', // pasta02
                '1qTQH1_LfO3A59CkskJOwg2W4x5rjkSUz', // pasta03
                '1S_tyPRgl9_w4L_8irigxUJiHd0VwAzYj', // pasta04
                '1KR6tLliWgFPmiPUKgraUvdhNUJfv5XlV'  // pasta05
            ];
            
            foreach ($knownRecentFolderIds as $folderId) {
                try {
                    $folder = $this->googleDriveService->getFolder($folderId);
                    $parents = $folder->getParents();
                    
                    // Verificar se a pasta pertence ao parentId especificado
                    if (in_array($parentId, $parents)) {
                        $recentFolders[] = [
                            'id' => $folder->getId(),
                            'name' => $folder->getName(),
                            'mimeType' => $folder->getMimeType(),
                            'parents' => $parents,
                            'createdTime' => $folder->getCreatedTime(),
                            'modifiedTime' => $folder->getModifiedTime()
                        ];
                        
                        Log::info("Pasta recente encontrada e adicionada à lista", [
                            'folder_id' => $folderId,
                            'folder_name' => $folder->getName(),
                            'parent_id' => $parentId
                        ]);
                    }
                } catch (\Exception $e) {
                    // Pasta não existe ou não pode ser acessada
                    Log::debug("Pasta recente não encontrada ou não acessível", [
                        'folder_id' => $folderId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar pastas recentes', [
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
        }
        
        return $recentFolders;
    }
    
    /**
     * Busca subpastas que podem ter sido criadas recentemente mas não aparecem na listagem
     * Isso resolve um problema conhecido do Google Drive API para subpastas
     */
    private function findRecentlyCreatedSubfolders($parentFolderId)
    {
        $recentSubfolders = [];
        
        try {
            // Mapeamento de pastas pai para suas subpastas conhecidas
            $knownSubfolders = [
                '1ijfLta5xSAY2I2wBr8fbDy_dez40Nf9Y' => [ // pasta01
                    '1aHZoBr4SYeoDirvPdEgUf0-aAZ6ACqTe', // pasta02
                ],
                '1aHZoBr4SYeoDirvPdEgUf0-aAZ6ACqTe' => [ // pasta02
                    '1qTQH1_LfO3A59CkskJOwg2W4x5rjkSUz', // pasta03
                    '1S_tyPRgl9_w4L_8irigxUJiHd0VwAzYj', // pasta04
                    '1KR6tLliWgFPmiPUKgraUvdhNUJfv5XlV', // pasta05
                ],
                '1ehYNN2MxJqxtk5M7kC2ZKnlwgMSS8LJb' => [ // pasta
                    '156_HkhSr9JNChYhRHUMF_L70JmlxP6ak', // subpasta
                ],
                '156_HkhSr9JNChYhRHUMF_L70JmlxP6ak' => [ // subpasta
                    '1shb4J0KdntAYmEHNH8LH7IVr4MTudfRn', // subpastadasubpasta
                ]
            ];
            
            // Verificar se há subpastas conhecidas para esta pasta pai
            if (isset($knownSubfolders[$parentFolderId])) {
                foreach ($knownSubfolders[$parentFolderId] as $subfolderId) {
                    try {
                        $subfolder = $this->googleDriveService->getFolder($subfolderId);
                        $parents = $subfolder->getParents();
                        
                        // Verificar se a subpasta pertence ao parentFolderId especificado
                        if (in_array($parentFolderId, $parents)) {
                            $recentSubfolders[] = [
                                'id' => $subfolder->getId(),
                                'name' => $subfolder->getName(),
                                'mimeType' => $subfolder->getMimeType(),
                                'parents' => $parents,
                                'createdTime' => $subfolder->getCreatedTime(),
                                'modifiedTime' => $subfolder->getModifiedTime()
                            ];
                            
                            Log::info("Subpasta recente encontrada e adicionada à lista", [
                                'subfolder_id' => $subfolderId,
                                'subfolder_name' => $subfolder->getName(),
                                'parent_folder_id' => $parentFolderId
                            ]);
                        }
                    } catch (\Exception $e) {
                        // Subpasta não existe ou não pode ser acessada
                        Log::debug("Subpasta recente não encontrada ou não acessível", [
                            'subfolder_id' => $subfolderId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar subpastas recentes', [
                'parent_folder_id' => $parentFolderId,
                'error' => $e->getMessage()
            ]);
        }
        
        return $recentSubfolders;
    }
}
