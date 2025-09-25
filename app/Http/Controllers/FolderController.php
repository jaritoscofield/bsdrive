<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Services\GoogleDriveSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\GoogleDriveService;

class FolderController extends Controller
{
    private $syncService;
    private $googleDriveService;

    public function __construct(GoogleDriveSyncService $syncService, GoogleDriveService $googleDriveService)
    {
        $this->syncService = $syncService;
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $parentId = $request->get('parent_id');
        $folders = [];
        $parentFolder = null;

        if (!$parentId) {
            // Listar apenas as pastas que o usuário tem permissão no banco (user_folders)
            $userFolderIds = $user->userFolders()->pluck('google_drive_folder_id')->toArray();
            
            if (empty($userFolderIds)) {
                return view('folders.index', compact('folders', 'parentFolder'))
                    ->with('warning', 'Você não tem acesso a nenhuma pasta.');
            }
            
            // Buscar informações das pastas no banco (Folder) usando os google_drive_id
            $folders = \App\Models\Folder::whereIn('google_drive_id', $userFolderIds)->where('active', true)->get();
            
            // Se não encontrou pastas no banco, buscar diretamente do Google Drive
            if ($folders->isEmpty()) {
                try {
                    $googleDriveService = app(\App\Services\GoogleDriveService::class);
                    $folders = collect($userFolderIds)->map(function($folderId) use ($googleDriveService) {
                        $folder = $googleDriveService->getFolder($folderId);
                        if ($folder) {
                            return (object) [
                                'name' => $folder->getName(),
                                'google_drive_id' => $folder->getId(),
                                'id' => $folder->getId(),
                                'active' => true
                            ];
                        }
                        return null;
                    })->filter()->values();
                } catch (\Exception $e) {
                    \Log::error('Error fetching folders from Google Drive:', ['error' => $e->getMessage()]);
                }
            }
        } else {
            // Se for subpasta, manter lógica anterior (opcional: pode restringir também)
            $parentFolder = \App\Models\Folder::find($parentId);
            if (!$parentFolder) {
                return view('folders.index', compact('folders', 'parentFolder'))
                    ->with('warning', 'Pasta não encontrada.');
            }
            // Listar subpastas do banco
            $folders = $parentFolder->children()->where('active', true)->get();
        }
        return view('folders.index', compact('folders', 'parentFolder'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $parentId = $request->get('parent_id');

        // Remover validação para admin_sistema
        if ($parentId && $user->role !== 'admin_sistema' && !$user->hasFolderAccess($parentId, 'write')) {
            abort(403, 'Você não tem permissão para criar pastas nesta localização.');
        }

        $folders = [];
        try {
            $accessibleFolderIds = $user->role === 'admin_sistema'
                ? collect($this->googleDriveService->listFiles(null, 'files(id,name,mimeType,parents)'))
                    ->filter(fn($file) => isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder')
                    ->pluck('id')
                    ->toArray()
                : $user->getAccessibleFolderIds('write');
            if (empty($accessibleFolderIds) && $user->role !== 'admin_sistema') {
                return back()->with('error', 'Você não tem permissão para criar pastas.');
            }
            $allFolders = $this->googleDriveService->listFiles(null, 'files(id,name,mimeType,parents)');
            $folders = array_filter($allFolders, function($file) use ($accessibleFolderIds) {
                return isset($file['mimeType']) &&
                       $file['mimeType'] === 'application/vnd.google-apps.folder' &&
                       in_array($file['id'], $accessibleFolderIds);
            });
            $folders = array_values($folders);
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar pastas do Google Drive para criação', ['error' => $e->getMessage()]);
        }
        // Buscar setores da empresa
        if ($user->role === 'admin_sistema') {
            $sectors = \App\Models\Sector::all();
        } else {
            $sectors = \App\Models\Sector::where('company_id', $user->company_id)->get();
        }
        return view('folders.create', compact('parentId', 'folders', 'sectors'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|string', // Agora é um ID do Google Drive
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if (!$user->company_id) {
            return back()->withErrors(['company_id' => 'Seu usuário não está associado a uma empresa. Contate o administrador.'])->withInput();
        }

        $parentId = $request->filled('parent_id') ? $request->parent_id : null;
        // Permitir admin_sistema criar na raiz sem checagem de permissão
        if ($parentId && $user->role !== 'admin_sistema' && !$user->hasFolderAccess($parentId, 'write')) {
            return back()->withErrors(['parent_id' => 'Você não tem permissão para criar pastas nesta localização.'])->withInput();
        }
        // Se for raiz (parentId null) e não for admin_sistema, bloquear
        if (!$parentId && $user->role !== 'admin_sistema') {
            return back()->withErrors(['parent_id' => 'Você não tem permissão para criar pastas na raiz.'])->withInput();
        }

        try {
            $folder = $this->googleDriveService->createFolder($request->name, $parentId);
            if ($folder) {
                $sectorId = $request->input('sector_id');
                $sector = \App\Models\Sector::find($sectorId);
                if (!$sector) {
                    return back()->withErrors(['sector_id' => 'Setor inválido.'])->withInput();
                }
                $folderModel = new \App\Models\Folder();
                $folderModel->name = $folder->getName();
                $folderModel->google_drive_id = $folder->getId();
                $folderModel->parent_id = $parentId;
                $folderModel->company_id = $user->company_id;
                $folderModel->sector_id = $sector->id;
                $folderModel->path = $folder->getName();
                $folderModel->active = true;
                $folderModel->save();
                return redirect()->route('folders.show', $folderModel->id)
                    ->with('success', 'Pasta criada com sucesso no Google Drive!');
            } else {
                return back()->withErrors(['name' => 'Erro ao criar pasta no Google Drive.'])->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Erro ao criar pasta no Google Drive', [
                'name' => $request->name,
                'parent_id' => $request->parent_id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['name' => 'Erro ao criar pasta no Google Drive: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = Auth::user();

        // Verificar se o usuário tem acesso à pasta
        if (!$user->canAccessCompanyFolder($id)) {
            abort(403, 'Você não tem permissão para acessar esta pasta.');
        }

        // Buscar pasta do Google Drive
        $folder = $this->googleDriveService->getFolder($id);

        // Buscar subpastas
        $subfolders = $this->googleDriveService->listFiles($id, 'files(id,name,mimeType,parents)');
        $subfolders = array_filter($subfolders, function($file) {
            return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
        });
        $subfolders = array_values($subfolders);

        // Buscar arquivos
        $files = $this->googleDriveService->listFiles($id, 'files(id,name,mimeType,parents,size,createdTime,modifiedTime)');
        $files = array_filter($files, function($file) {
            return isset($file['mimeType']) && $file['mimeType'] !== 'application/vnd.google-apps.folder';
        });
        $files = array_values($files);

        return view('folders.show', compact('folder', 'subfolders', 'files'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = Auth::user();

        if (!$user->company_id) {
            abort(403, 'Seu usuário não está associado a uma empresa.');
        }

        // Verificar se o usuário tem permissão de escrita na pasta
        if (!$user->hasFolderAccess($id, 'write')) {
            abort(403, 'Você não tem permissão para editar esta pasta.');
        }

        try {
            // Buscar pasta do Google Drive
            $folder = $this->googleDriveService->getFolder($id);

            if (!$folder) {
                abort(404, 'Pasta não encontrada no Google Drive.');
            }

            // Converter objeto Google Drive para array
            $folderModel = \App\Models\Folder::where('google_drive_id', $id)->first();
            $folderArray = [
                'id' => $folder->getId(),
                'name' => $folder->getName(),
                'mimeType' => $folder->getMimeType(),
                'parents' => $folder->getParents() ? $folder->getParents() : [],
                'sector_id' => $folderModel ? $folderModel->sector_id : null,
            ];
            // Buscar setores da empresa
            if ($user->role === 'admin_sistema') {
                $sectors = \App\Models\Sector::all();
            } else {
                $sectors = \App\Models\Sector::where('company_id', $user->company_id)->get();
            }
            // Buscar outras pastas do Google Drive para o select de pasta pai
            $folders = [];
            try {
                $accessibleFolderIds = $user->getAccessibleFolderIds('write');
                $allFolders = $this->googleDriveService->listFiles(null, 'files(id,name,mimeType,parents)');
                $folders = array_filter($allFolders, function($file) use ($id, $accessibleFolderIds) {
                    return isset($file['mimeType']) &&
                           $file['mimeType'] === 'application/vnd.google-apps.folder' &&
                           $file['id'] !== $id && // Excluir a própria pasta
                           in_array($file['id'], $accessibleFolderIds);
                });
                $folders = array_values($folders);
            } catch (\Exception $e) {
                Log::warning('Erro ao buscar pastas do Google Drive para edição', ['error' => $e->getMessage()]);
            }

            return view('folders.edit', compact('folderArray', 'folders', 'sectors'));
        } catch (\Exception $e) {
            Log::error('Erro ao buscar pasta do Google Drive para edição', [
                'folder_id' => $id,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Erro ao carregar pasta do Google Drive.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->company_id) {
            abort(403, 'Seu usuário não está associado a uma empresa.');
        }

        // Verificar se o usuário tem permissão de escrita na pasta
        if (!$user->hasFolderAccess($id, 'write')) {
            abort(403, 'Você não tem permissão para editar esta pasta.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|string',
            'sector_id' => 'required|exists:sectors,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verificar permissão de escrita na pasta pai (se for mover)
        $parentId = $request->filled('parent_id') ? $request->parent_id : null;
        if ($parentId && !$user->hasFolderAccess($parentId, 'write')) {
            return back()->withErrors(['parent_id' => 'Você não tem permissão para mover a pasta para esta localização.'])->withInput();
        }

        try {
            // Atualizar pasta no Google Drive
            $updateData = [
                'name' => $request->name
            ];

            if ($request->filled('parent_id')) {
                $updateData['parent_id'] = $request->parent_id;
            }

            $updatedFolder = $this->googleDriveService->updateFolder($id, $updateData);

            if ($updatedFolder) {
                // Atualizar setor no banco
                $folderModel = \App\Models\Folder::where('google_drive_id', $id)->first();
                if ($folderModel) {
                    $folderModel->sector_id = $request->sector_id;
                    $folderModel->save();
                }
                return redirect()->route('folders.show', $id)
                    ->with('success', 'Pasta atualizada com sucesso no Google Drive!');
            } else {
                return back()->withErrors(['name' => 'Erro ao atualizar pasta no Google Drive.'])->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar pasta no Google Drive', [
                'folder_id' => $id,
                'name' => $request->name,
                'parent_id' => $request->parent_id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['name' => 'Erro ao atualizar pasta no Google Drive: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user->company_id) {
            abort(403, 'Seu usuário não está associado a uma empresa.');
        }

        // Verificar se o usuário tem permissão de administração na pasta
        if (!$user->hasFolderAccess($id, 'admin')) {
            abort(403, 'Você não tem permissão para excluir esta pasta.');
        }

        try {
            // Excluir pasta do Google Drive (pastas são tratadas como arquivos especiais)
            $deleted = $this->googleDriveService->deleteFile($id);

            if ($deleted) {
                return redirect()->route('folders.index')
                    ->with('success', 'Pasta excluída com sucesso do Google Drive!');
            } else {
                return back()->with('error', 'Erro ao excluir pasta do Google Drive.');
            }
        } catch (\Exception $e) {
            Log::error('Erro ao excluir pasta do Google Drive', [
                'folder_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erro ao excluir pasta do Google Drive: ' . $e->getMessage());
        }
    }

    /**
     * Get folder tree for navigation.
     */
    public function tree()
    {
        $user = Auth::user();
        $query = Folder::notDeleted();

        if ($user->role !== 'admin_sistema') {
            $query->byCompany($user->company_id);
        }

        $folders = $query->with(['children' => function ($query) {
            $query->notDeleted()->withCount(['files', 'children']);
        }])
        ->root()
        ->withCount(['files', 'children'])
        ->orderBy('name')
        ->get();

        return response()->json($folders);
    }

    /**
     * Get folder statistics.
     */
    public function statistics()
    {
        $user = Auth::user();
        $query = Folder::notDeleted();

        if ($user->role !== 'admin_sistema') {
            $query->byCompany($user->company_id);
        }

        $stats = [
            'total_folders' => $query->count(),
            'root_folders' => $query->root()->count(),
            'total_files' => $query->with('files')->get()->sum(function ($folder) {
                return $folder->files->count();
            }),
            'total_size' => $query->with('files')->get()->sum(function ($folder) {
                return $folder->files->sum('size');
            }),
        ];

        return response()->json($stats);
    }
}
