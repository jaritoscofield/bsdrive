<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Folder;
use App\Services\GoogleDriveService;
use App\Services\GoogleDriveSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;


class GoogleDriveController extends Controller
{
    private $googleDriveService;
    private $syncService;

    public function __construct(GoogleDriveService $googleDriveService, GoogleDriveSyncService $syncService)
    {
        $this->googleDriveService = $googleDriveService;
        $this->syncService = $syncService;
    }

    /**
     * Exibe a página de configuração do Google Drive
     */
    public function index()
    {
        $user = Auth::user();

        // Buscar estatísticas de sincronização
        $totalFiles = File::byCompany($user->company_id)->count();
        $syncedFiles = File::byCompany($user->company_id)->whereNotNull('google_drive_id')->count();
        $totalFolders = Folder::byCompany($user->company_id)->count();
        $syncedFolders = Folder::byCompany($user->company_id)->whereNotNull('google_drive_id')->count();

        return view('google-drive.index', compact('totalFiles', 'syncedFiles', 'totalFolders', 'syncedFolders'));
    }

    /**
     * Lista arquivos e pastas do Google Drive
     */
    public function listFiles(Request $request)
    {
        try {
            $folderId = $request->get('folder_id');
            $onlyFolders = $request->boolean('only_folders');
            $fields = 'files(id,name,mimeType,parents)';
            $files = $this->googleDriveService->listFiles($folderId, $fields);

            if ($onlyFolders) {
                $files = array_filter($files, function($file) {
                    return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
                });
                $files = array_values($files);
            }

            return response()->json([
                'success' => true,
                'data' => $files
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar arquivos do Google Drive', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar arquivos do Google Drive: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincroniza uma pasta específica com o Google Drive
     */
    public function syncFolder(Request $request, Folder $folder)
    {
        try {
            $user = Auth::user();

            if (!$folder->canBeModifiedBy($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para sincronizar esta pasta.'
                ], 403);
            }

            $this->syncService->syncFolder($folder);

            return response()->json([
                'success' => true,
                'message' => 'Pasta sincronizada com sucesso!'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar pasta', [
                'folder_id' => $folder->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao sincronizar pasta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincroniza um arquivo específico com o Google Drive
     */
    public function syncFile(Request $request, File $file)
    {
        try {
            $user = Auth::user();

            if (!$file->canBeModifiedBy($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para sincronizar este arquivo.'
                ], 403);
            }

            $this->syncService->syncFile($file, $file->folder?->google_drive_id);

            return response()->json([
                'success' => true,
                'message' => 'Arquivo sincronizado com sucesso!'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar arquivo', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao sincronizar arquivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincroniza toda a empresa com o Google Drive
     */
    public function syncCompany(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'admin_sistema' && $user->role !== 'admin_empresa') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para sincronizar a empresa.'
                ], 403);
            }

            $companyId = $user->role === 'admin_sistema'
                ? $request->get('company_id', $user->company_id)
                : $user->company_id;

            $this->syncService->syncCompany($companyId);

            return response()->json([
                'success' => true,
                'message' => 'Empresa sincronizada com sucesso!'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar empresa', [
                'company_id' => $user->company_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao sincronizar empresa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Importa dados do Google Drive para o sistema local
     */
    public function importFromGoogleDrive(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'admin_sistema' && $user->role !== 'admin_empresa') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para importar dados.'
                ], 403);
            }

            $request->validate([
                'google_drive_folder_id' => 'required|string',
                'local_folder_id' => 'nullable|exists:folders,id',
            ]);

            $companyId = $user->role === 'admin_sistema'
                ? $request->get('company_id', $user->company_id)
                : $user->company_id;

            $this->syncService->importFromGoogleDrive(
                $request->google_drive_folder_id,
                $request->local_folder_id,
                $companyId
            );

            return response()->json([
                'success' => true,
                'message' => 'Dados importados com sucesso!'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao importar do Google Drive', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao importar dados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Testa a conexão com o Google Drive
     */
    public function testConnection()
    {
        try {
            // Tenta listar arquivos da raiz
            $files = $this->googleDriveService->listFiles(null, 'files(id,name)');

            return response()->json([
                'success' => true,
                'message' => 'Conexão com Google Drive estabelecida com sucesso!',
                'files_count' => count($files)
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão com Google Drive', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro na conexão com Google Drive: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca informações de um arquivo no Google Drive
     */
    public function getFileInfo(Request $request)
    {
        try {
            $request->validate([
                'file_id' => 'required|string'
            ]);

            $file = $this->googleDriveService->getFile($request->file_id);

            return response()->json([
                'success' => true,
                'data' => $file
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar informações do arquivo', [
                'file_id' => $request->file_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar informações do arquivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca informações de uma pasta no Google Drive
     */
    public function getFolderInfo(Request $request)
    {
        try {
            $request->validate([
                'folder_id' => 'required|string'
            ]);

            $folder = $this->googleDriveService->getFolder($request->folder_id);

            return response()->json([
                'success' => true,
                'data' => $folder
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar informações da pasta', [
                'folder_id' => $request->folder_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar informações da pasta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria uma nova pasta no Google Drive
     */
    public function createFolder(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'parent_id' => 'nullable|string'
            ]);

            $folder = $this->googleDriveService->createFolder(
                $request->name,
                $request->parent_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Pasta criada com sucesso!',
                'data' => $folder
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao criar pasta no Google Drive', [
                'name' => $request->name,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pasta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove um arquivo ou pasta do Google Drive
     */
    public function deleteFromGoogleDrive(Request $request)
    {
        try {
            $request->validate([
                'file_id' => 'required|string'
            ]);

            $this->googleDriveService->deleteFile($request->file_id);

            return response()->json([
                'success' => true,
                'message' => 'Item removido do Google Drive com sucesso!'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao remover do Google Drive', [
                'file_id' => $request->file_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover do Google Drive: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Redireciona para o login do Google
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/drive'])
            ->redirect();
    }

    /**
     * Callback do Google OAuth2
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            // Salvar token na sessão (ou banco de dados)
            Session::put('google_drive_token', $googleUser->token);
            Session::put('google_drive_refresh_token', $googleUser->refreshToken);
            Session::put('google_drive_token_expires_in', $googleUser->expiresIn);
            return redirect()->route('google-drive.index')->with('success', 'Google Drive conectado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('google-drive.index')->with('error', 'Erro ao conectar com o Google Drive: ' . $e->getMessage());
        }
    }


}
