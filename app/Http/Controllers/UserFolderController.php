<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserFolder;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserFolderController extends Controller
{
    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(User $user)
    {
        $userFolders = $user->userFolders()->with('user')->get();

        return view('user-folders.index', compact('user', 'userFolders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(User $user)
    {
        try {
            // Buscar pastas disponíveis no Google Drive
            $allFolders = $this->googleDriveService->listFiles(null, 'files(id,name,mimeType,parents)');
            $folders = array_filter($allFolders, function($file) {
                return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
            });
            $folders = array_values($folders);

            // Filtrar apenas pastas que o usuário ainda não tem acesso
            $accessibleFolderIds = $user->getAccessibleFolderIds();
            $availableFolders = collect($folders)->filter(function ($folder) use ($accessibleFolderIds) {
                return !in_array($folder['id'], $accessibleFolderIds);
            })->values();

            return view('user-folders.create', compact('user', 'availableFolders'));
        } catch (\Exception $e) {
            Log::error('Erro ao buscar pastas do Google Drive: ' . $e->getMessage());
            return redirect()->route('users.folders.index', $user)
                           ->with('error', 'Erro ao buscar pastas do Google Drive.');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, User $user)
    {
        $request->validate([
            'google_drive_folder_id' => 'required|string',
            'folder_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permission_level' => 'required|in:read,write,admin',
        ]);

        try {
            // Verificar se o usuário já tem acesso a esta pasta
            if ($user->hasFolderAccess($request->google_drive_folder_id)) {
                return back()->withInput()->with('error', 'O usuário já tem acesso a esta pasta.');
            }

            // Verificar se a pasta existe no Google Drive
            $folder = $this->googleDriveService->getFolder($request->google_drive_folder_id);
            if (!$folder) {
                return back()->withInput()->with('error', 'Pasta não encontrada no Google Drive.');
            }

            // Verificar se a empresa do usuário tem acesso à pasta
            if ($user->company && !$user->company->hasFolderAccess($request->google_drive_folder_id)) {
                return back()->withInput()->with('error', 'A empresa do usuário não tem acesso a esta pasta.');
            }

            // Criar a permissão
            $userFolder = $user->userFolders()->create([
                'google_drive_folder_id' => $request->google_drive_folder_id,
                'folder_name' => $request->folder_name,
                'description' => $request->description,
                'permission_level' => $request->permission_level,
                'active' => true,
            ]);

            return redirect()->route('users.folders.index', $user)
                           ->with('success', 'Permissão de pasta adicionada com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao criar permissão de pasta: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erro ao criar permissão de pasta.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user, UserFolder $userFolder)
    {
        return view('user-folders.show', compact('user', 'userFolder'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user, UserFolder $userFolder)
    {
        return view('user-folders.edit', compact('user', 'userFolder'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user, UserFolder $userFolder)
    {
        $request->validate([
            'folder_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permission_level' => 'required|in:read,write,admin',
            'active' => 'boolean',
        ]);

        try {
            $userFolder->update([
                'folder_name' => $request->folder_name,
                'description' => $request->description,
                'permission_level' => $request->permission_level,
                'active' => $request->has('active'),
            ]);

            return redirect()->route('users.folders.index', $user)
                           ->with('success', 'Permissão de pasta atualizada com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar permissão de pasta: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erro ao atualizar permissão de pasta.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user, UserFolder $userFolder)
    {
        try {
            $userFolder->delete();

            return redirect()->route('users.folders.index', $user)
                           ->with('success', 'Permissão de pasta removida com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao remover permissão de pasta: ' . $e->getMessage());
            return back()->with('error', 'Erro ao remover permissão de pasta.');
        }
    }

    /**
     * Ativar/desativar permissão de pasta
     */
    public function toggle(User $user, UserFolder $userFolder)
    {
        try {
            $userFolder->update([
                'active' => !$userFolder->active
            ]);

            $status = $userFolder->active ? 'ativada' : 'desativada';
            return redirect()->route('users.folders.index', $user)
                           ->with('success', "Permissão de pasta {$status} com sucesso.");
        } catch (\Exception $e) {
            Log::error('Erro ao alterar status da permissão: ' . $e->getMessage());
            return back()->with('error', 'Erro ao alterar status da permissão.');
        }
    }

    /**
     * Gerenciar permissões de pastas em lote
     */
    public function bulkAssign(Request $request, User $user)
    {
        $request->validate([
            'folder_ids' => 'required|array',
            'folder_ids.*' => 'string',
            'permission_level' => 'required|in:read,write,admin',
        ]);

        try {
            $allFolders = $this->googleDriveService->listFiles(null, 'files(id,name,mimeType,parents)');
            $folders = array_filter($allFolders, function($file) {
                return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
            });
            $folders = array_values($folders);
            $foldersMap = collect($folders)->keyBy('id');

            $created = 0;
            $skipped = 0;

            foreach ($request->folder_ids as $folderId) {
                // Verificar se o usuário já tem acesso
                if ($user->hasFolderAccess($folderId)) {
                    $skipped++;
                    continue;
                }

                // Verificar se a pasta existe
                if (!$foldersMap->has($folderId)) {
                    continue;
                }

                $folder = $foldersMap->get($folderId);

                // Verificar se a empresa tem acesso
                if ($user->company && !$user->company->hasFolderAccess($folderId)) {
                    continue;
                }

                // Criar permissão
                $user->userFolders()->create([
                    'google_drive_folder_id' => $folderId,
                    'folder_name' => $folder['name'],
                    'permission_level' => $request->permission_level,
                    'active' => true,
                ]);

                $created++;
            }

            $message = "{$created} permissões criadas com sucesso.";
            if ($skipped > 0) {
                $message .= " {$skipped} pastas já tinham acesso.";
            }

            return redirect()->route('users.folders.index', $user)
                           ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Erro ao atribuir permissões em lote: ' . $e->getMessage());
            return back()->with('error', 'Erro ao atribuir permissões em lote.');
        }
    }
}
