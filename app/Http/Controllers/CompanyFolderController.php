<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyFolder;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CompanyFolderController extends Controller
{
    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Company $company)
    {
        $companyFolders = $company->companyFolders()->with('company')->get();

        return view('company-folders.index', compact('company', 'companyFolders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Company $company)
    {
        try {
            // Buscar pastas disponíveis no Google Drive
            $allFolders = $this->googleDriveService->listFiles(null, 'files(id,name,mimeType,parents)');
            $folders = array_filter($allFolders, function($file) {
                return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
            });
            $folders = array_values($folders);

            // Filtrar apenas pastas que a empresa ainda não tem acesso
            $accessibleFolderIds = $company->getAccessibleFolderIds();
            $availableFolders = collect($folders)->filter(function ($folder) use ($accessibleFolderIds) {
                return !in_array($folder['id'], $accessibleFolderIds);
            })->values();

            return view('company-folders.create', compact('company', 'availableFolders'));
        } catch (\Exception $e) {
            Log::error('Erro ao buscar pastas do Google Drive: ' . $e->getMessage());
            return redirect()->route('companies.folders.index', $company)
                           ->with('error', 'Erro ao buscar pastas do Google Drive.');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Company $company)
    {
        $request->validate([
            'google_drive_folder_id' => 'required|string',
            'folder_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            // Verificar se a empresa já tem acesso a esta pasta
            if ($company->hasFolderAccess($request->google_drive_folder_id)) {
                return back()->withInput()->with('error', 'A empresa já tem acesso a esta pasta.');
            }

            // Verificar se a pasta existe no Google Drive
            $folder = $this->googleDriveService->getFolder($request->google_drive_folder_id);
            if (!$folder) {
                return back()->withInput()->with('error', 'Pasta não encontrada no Google Drive.');
            }

            // Criar a permissão
            $companyFolder = $company->companyFolders()->create([
                'google_drive_folder_id' => $request->google_drive_folder_id,
                'folder_name' => $request->folder_name,
                'description' => $request->description,
                'active' => true,
            ]);

            return redirect()->route('companies.folders.index', $company)
                           ->with('success', 'Permissão de pasta adicionada com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao criar permissão de pasta: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erro ao criar permissão de pasta.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company, CompanyFolder $companyFolder)
    {
        return view('company-folders.show', compact('company', 'companyFolder'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company, CompanyFolder $companyFolder)
    {
        return view('company-folders.edit', compact('company', 'companyFolder'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company, CompanyFolder $companyFolder)
    {
        $request->validate([
            'folder_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        try {
            $companyFolder->update([
                'folder_name' => $request->folder_name,
                'description' => $request->description,
                'active' => $request->has('active'),
            ]);

            return redirect()->route('companies.folders.index', $company)
                           ->with('success', 'Permissão de pasta atualizada com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar permissão de pasta: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erro ao atualizar permissão de pasta.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company, CompanyFolder $companyFolder)
    {
        try {
            $companyFolder->delete();

            return redirect()->route('companies.folders.index', $company)
                           ->with('success', 'Permissão de pasta removida com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao remover permissão de pasta: ' . $e->getMessage());
            return back()->with('error', 'Erro ao remover permissão de pasta.');
        }
    }

    /**
     * Ativar/desativar permissão de pasta
     */
    public function toggle(Company $company, CompanyFolder $companyFolder)
    {
        try {
            $companyFolder->update([
                'active' => !$companyFolder->active
            ]);

            $status = $companyFolder->active ? 'ativada' : 'desativada';
            return redirect()->route('companies.folders.index', $company)
                           ->with('success', "Permissão de pasta {$status} com sucesso.");
        } catch (\Exception $e) {
            Log::error('Erro ao alterar status da permissão: ' . $e->getMessage());
            return back()->with('error', 'Erro ao alterar status da permissão.');
        }
    }
}
