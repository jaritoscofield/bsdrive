<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Folder;
use App\Services\GoogleDriveSyncService;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Response;

class FileController extends Controller
{
    private $syncService;
    private $googleDriveService;

    public function __construct(GoogleDriveSyncService $syncService, GoogleDriveService $googleDriveService)
    {
        \Log::emergency("🚨 FileController constructor called");
        $this->syncService = $syncService;
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $folderId = $request->get('folder_id');
        $files = [];
        $folders = [];
        $parentFolder = $folderId ? $this->googleDriveService->getFolder($folderId) : null;

        try {
            $accessibleFolderIds = $user->getAccessibleFolderIds();
            if (empty($accessibleFolderIds)) {
                return view('files.index', compact('files', 'folders', 'parentFolder'))
                    ->with('warning', 'Você não tem acesso a nenhuma pasta.');
            }
            // Buscar arquivos e pastas do BSDrive
            if ($folderId) {
                if (!$user->canAccessCompanyFolder($folderId)) {
                    abort(403, 'Você não tem permissão para acessar esta pasta.');
                }
                $allItems = $this->googleDriveService->listFiles($folderId, 'files(id,name,mimeType,size,createdTime,modifiedTime,parents)');
            } else {
                $allItems = [];
                foreach ($accessibleFolderIds as $id) {
                    $allItems = array_merge($allItems, $this->googleDriveService->listFiles($id, 'files(id,name,mimeType,size,createdTime,modifiedTime,parents)'));
                }
            }
            $folders = array_filter($allItems, function($item) {
                return isset($item['mimeType']) && $item['mimeType'] === 'application/vnd.google-apps.folder';
            });
            $folders = array_values($folders);
            $files = array_filter($allItems, function($item) {
                return isset($item['mimeType']) && $item['mimeType'] !== 'application/vnd.google-apps.folder';
            });
            $files = array_values($files);
        } catch (\Exception $e) {
            \Log::warning('Erro ao buscar arquivos do BSDrive', ['error' => $e->getMessage()]);
        }

        return view('files.index', compact('files', 'folders', 'parentFolder'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        \Log::emergency("🚨 FileController CREATE method called");
        $user = Auth::user();
        \Log::emergency("User ID: " . $user->id . ", Company ID: " . $user->company_id);

        if (!$user->company_id) {
            \Log::emergency("ERRO: Usuário sem company_id - redirecionando");
            return back()->withErrors(['company_id' => 'Seu usuário não está associado a uma empresa.']);
        }

        try {
            // TEMPORÁRIO: Pular verificação de permissões e usar apenas pasta pessoal
            $folders = [];
            
            \Log::emergency("Renderizando view files.create com pasta vazia (upload direto para pasta pessoal)");
            return view('files.create', compact('folders'));
        } catch (\Exception $e) {
            \Log::error('Erro ao carregar página de criação de arquivos', [
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['error' => 'Erro ao carregar página de criação: ' . $e->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Log básico para verificar se está chegando aqui
        \Log::emergency('=== FILECONTROLLER STORE CHAMADO ===');
        \Log::emergency('REQUEST METHOD: ' . $request->method());
        \Log::emergency('HAS FILES: ' . ($request->hasFile('files') ? 'SIM' : 'NÃO'));
        \Log::emergency('REQUEST ALL: ' . json_encode($request->all()));
        \Log::emergency('FILES COUNT: ' . ($request->hasFile('files') ? count($request->file('files')) : 0));

        // Se não tem arquivos, retorna erro imediatamente
        if (!$request->hasFile('files')) {
            \Log::emergency('ERRO: Nenhum arquivo enviado');
            return back()->withErrors(['files' => 'Nenhum arquivo foi selecionado.'])->withInput();
        }

        $user = Auth::user();
        \Log::emergency('USER ID: ' . $user->id);
        \Log::emergency('USER COMPANY ID: ' . $user->company_id);
        \Log::emergency('USER ROLE: ' . $user->role);
        \Log::emergency('USER COMPANY: ' . $user->company_id);
        
        // Verificar se usuário tem empresa
        if (!$user->company_id) {
            \Log::emergency('ERRO: Usuário sem empresa');
            return back()->withErrors(['error' => 'Seu usuário não está associado a uma empresa.'])->withInput();
        }

        // Validação simples
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:51200', // 50MB
        ]);

        \Log::emergency('VALIDAÇÃO PASSOU');

        $uploadedFiles = [];
        $errors = [];

        // Get personal folder ID
        $personalFolderId = $user->getPersonalFolderId();
        \Log::emergency('PASTA PESSOAL ID: ' . $personalFolderId);
        
        // TEMPORÁRIO: Se não tiver Shared Drive configurado, usar pasta da empresa
        $sharedDriveId = config('services.google.shared_drive_id');
        \Log::emergency('🔧 CONFIGURAÇÕES DO SISTEMA:', [
            'shared_drive_id' => $sharedDriveId ?: 'NÃO CONFIGURADO',
            'service_account_file' => config('services.google.service_account_file'),
            'pasta_pessoal_usuario' => $personalFolderId
        ]);
        
        if (empty($sharedDriveId)) {
            \Log::emergency('⚠️  Shared Drive não configurado, buscando pasta da empresa...');
            $company = $user->company;
            \Log::emergency('DADOS DA EMPRESA:', [
                'company_exists' => $company ? 'SIM' : 'NÃO',
                'company_id' => $company ? $company->id : 'N/A',
                'company_name' => $company ? $company->name : 'N/A',
                'company_folder_id' => $company ? $company->google_drive_folder_id : 'N/A'
            ]);
            
            if ($company && $company->google_drive_folder_id) {
                $personalFolderId = $company->google_drive_folder_id;
                \Log::emergency('📁 Usando pasta da empresa: ' . $personalFolderId);
            } else {
                \Log::emergency('⚠️  Nem empresa nem Shared Drive configurados - FORÇANDO upload na pasta pessoal');
                \Log::emergency('💡 AVISO: Pode dar erro de quota, mas vamos tentar...');
                // Continua com a pasta pessoal original
            }
        } else {
            \Log::emergency('✅ Shared Drive configurado, usando pasta pessoal normalmente');
        }

        foreach ($request->file('files') as $index => $file) {
            \Log::emergency('PROCESSANDO ARQUIVO ' . ($index + 1) . ': ' . $file->getClientOriginalName());
            
            try {
                // Detectar MIME type de forma segura
                $mimeType = $this->detectMimeType($file);
                \Log::emergency('MIME TYPE DETECTADO: ' . $mimeType);
                
                // Upload para pasta pessoal do usuário
                $uploadedFile = $this->googleDriveService->uploadFile(
                    $file->getPathname(),
                    $file->getClientOriginalName(),
                    $personalFolderId, // Sempre usar pasta pessoal
                    $mimeType
                );

                $uploadedFiles[] = $uploadedFile;
                \Log::emergency('ARQUIVO ENVIADO COM SUCESSO: ' . $uploadedFile->getId());
                
            } catch (\Exception $e) {
                // Log detalhado do erro
                \Log::emergency('❌ ERRO DETALHADO NO UPLOAD', [
                    'arquivo' => $file->getClientOriginalName(),
                    'erro_completo' => $e->getMessage(),
                    'codigo_erro' => $e->getCode(),
                    'tipo_erro' => get_class($e),
                    'linha_erro' => $e->getLine(),
                    'arquivo_erro' => $e->getFile(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Tratamento específico para diferentes tipos de erro
                $errorMessage = $this->formatErrorMessage($e, $file->getClientOriginalName());
                $errors[] = $errorMessage;
            }
        }

        if (!empty($errors)) {
            \Log::emergency('🚨 UPLOAD FINALIZADO COM ERROS:', [
                'total_arquivos' => count($request->file('files')),
                'arquivos_com_sucesso' => count($uploadedFiles),
                'arquivos_com_erro' => count($errors),
                'lista_erros' => $errors
            ]);
            
            // Mostrar erros de forma organizada
            $errorMessage = "Alguns arquivos não puderam ser enviados:\n\n";
            foreach ($errors as $error) {
                $errorMessage .= "• " . $error . "\n";
            }
            
            return back()->withErrors(['files' => $errorMessage])->withInput();
        }

        $message = count($uploadedFiles) === 1
            ? 'Arquivo enviado com sucesso para sua pasta pessoal!'
            : count($uploadedFiles) . ' arquivos enviados com sucesso para sua pasta pessoal!';

        \Log::emergency('UPLOAD CONCLUÍDO: ' . count($uploadedFiles) . ' arquivos');

        return redirect()->route('files.index')->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = Auth::user();

        if (!$user->company_id) {
            abort(403, 'Seu usuário não está associado a uma empresa.');
        }

        try {
            // Buscar arquivo do BSDrive
            $file = $this->googleDriveService->getFile($id);

            if (!$file) {
                abort(404, 'Arquivo não encontrado no BSDrive.');
            }

            // Verificar se o usuário tem acesso ao arquivo (através da pasta pai)
            $fileParents = $file->getParents() ? $file->getParents() : [];
            $hasAccess = false;

            foreach ($fileParents as $parentId) {
                if ($user->canAccessCompanyFolder($parentId)) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                abort(403, 'Você não tem permissão para acessar este arquivo.');
            }

            // Converter objeto BSDrive para array
            $fileArray = [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
                'createdTime' => $file->getCreatedTime(),
                'modifiedTime' => $file->getModifiedTime(),
                'parents' => $fileParents,
                'webContentLink' => $file->getWebContentLink()
            ];

            return view('files.show', compact('fileArray'));
        } catch (\Exception $e) {
            Log::error('Erro ao buscar arquivo do BSDrive', [
                'file_id' => $id,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Erro ao carregar arquivo do BSDrive.');
        }
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

        try {
            // Buscar arquivo do BSDrive
            $file = $this->googleDriveService->getFile($id);

            if (!$file) {
                abort(404, 'Arquivo não encontrado no BSDrive.');
            }

            // Verificar se o usuário tem acesso ao arquivo (através da pasta pai)
            $fileParents = $file->getParents() ? $file->getParents() : [];
            $hasAccess = false;

            foreach ($fileParents as $parentId) {
                if ($user->canAccessCompanyFolder($parentId)) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                abort(403, 'Você não tem permissão para acessar este arquivo.');
            }

            // Verificar se o usuário tem permissão de escrita
            $hasWriteAccess = false;
            foreach ($fileParents as $parentId) {
                if ($user->hasFolderAccess($parentId, 'write')) {
                    $hasWriteAccess = true;
                    break;
                }
            }

            if (!$hasWriteAccess) {
                abort(403, 'Você não tem permissão para editar este arquivo.');
            }

            // Converter objeto BSDrive para array
            $fileArray = [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
                'createdTime' => $file->getCreatedTime(),
                'modifiedTime' => $file->getModifiedTime(),
                'parents' => $fileParents,
                'webContentLink' => $file->getWebContentLink()
            ];

            // Buscar pastas do BSDrive para o select (apenas as que o usuário tem permissão de escrita)
            $folders = [];
            try {
                $accessibleFolderIds = $user->getAccessibleFolderIds('write');
                $allFolders = $this->googleDriveService->listFiles(null, 'files(id,name,mimeType,parents)');
                $folders = array_filter($allFolders, function($file) use ($accessibleFolderIds) {
                    return isset($file['mimeType']) &&
                           $file['mimeType'] === 'application/vnd.google-apps.folder' &&
                           in_array($file['id'], $accessibleFolderIds);
                });
                $folders = array_values($folders);
            } catch (\Exception $e) {
                Log::warning('Erro ao buscar pastas do BSDrive para edição', ['error' => $e->getMessage()]);
            }

            return view('files.edit', compact('fileArray', 'folders'));
        } catch (\Exception $e) {
            Log::error('Erro ao buscar arquivo do BSDrive para edição', [
                'file_id' => $id,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Erro ao carregar arquivo do BSDrive.');
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

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|string',
            'file' => 'nullable|file|max:102400', // 100MB max
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Buscar arquivo atual para verificar permissões
            $currentFile = $this->googleDriveService->getFile($id);
            if (!$currentFile) {
                abort(404, 'Arquivo não encontrado no BSDrive.');
            }

            // Verificar se o usuário tem permissão de escrita no arquivo atual
            $fileParents = $currentFile->getParents() ? $currentFile->getParents() : [];
            $hasWriteAccess = false;

            foreach ($fileParents as $parentId) {
                if ($user->hasFolderAccess($parentId, 'write')) {
                    $hasWriteAccess = true;
                    break;
                }
            }

            if (!$hasWriteAccess) {
                abort(403, 'Você não tem permissão para editar este arquivo.');
            }

            // Verificar permissão de escrita na pasta pai (se for mover)
            $newParentId = $request->filled('parent_id') ? $request->parent_id : null;
            if ($newParentId && !$user->hasFolderAccess($newParentId, 'write')) {
                return back()->withErrors(['parent_id' => 'Você não tem permissão para mover o arquivo para esta pasta.'])->withInput();
            }

            // Se um novo arquivo foi enviado
            if ($request->hasFile('file')) {
                // Deletar arquivo antigo do BSDrive
                $this->googleDriveService->deleteFile($id);

                // Fazer upload do novo arquivo
                $uploadedFile = $request->file('file');

                $newFile = $this->googleDriveService->uploadFile(
                    $uploadedFile->getPathname(),
                    $request->name,
                    $newParentId,
                    $uploadedFile->getMimeType()
                );

                return redirect()->route('files.show', $newFile->getId())
                    ->with('success', 'Arquivo atualizado com sucesso no BSDrive!');
            } else {
                // Apenas atualizar informações (nome e pasta pai)
                $updateData = [
                    'name' => $request->name
                ];

                // Se há mudança de pasta pai
                if ($request->filled('parent_id')) {
                    $previousParents = join(',', $currentFile->getParents());

                    $this->googleDriveService->moveFile($id, $newParentId);
                } else {
                    // Apenas renomear
                    $this->googleDriveService->renameFile($id, $request->name);
                }

                return redirect()->route('files.show', $id)
                    ->with('success', 'Arquivo atualizado com sucesso no BSDrive!');
            }
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar arquivo no BSDrive', [
                'file_id' => $id,
                'name' => $request->name,
                'parent_id' => $request->parent_id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['name' => 'Erro ao atualizar arquivo no BSDrive: ' . $e->getMessage()])->withInput();
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

        try {
            // Buscar arquivo do BSDrive para obter o nome
            $file = $this->googleDriveService->getFile($id);

            if (!$file) {
                abort(404, 'Arquivo não encontrado no BSDrive.');
            }

            // Verificar se o usuário tem permissão de administração no arquivo
            $fileParents = $file->getParents() ? $file->getParents() : [];
            $hasAdminAccess = false;

            foreach ($fileParents as $parentId) {
                if ($user->hasFolderAccess($parentId, 'admin')) {
                    $hasAdminAccess = true;
                    break;
                }
            }

            if (!$hasAdminAccess) {
                abort(403, 'Você não tem permissão para excluir este arquivo.');
            }

            $fileName = $file->getName();

            // Deletar arquivo do BSDrive
            $this->googleDriveService->deleteFile($id);

            return redirect()->route('files.index')
                ->with('success', "Arquivo '{$fileName}' excluído com sucesso do BSDrive!");
        } catch (\Exception $e) {
            Log::error('Erro ao deletar arquivo do BSDrive', [
                'file_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Erro ao deletar arquivo do BSDrive: ' . $e->getMessage()]);
        }
    }

    /**
     * Download the specified file from BSDrive.
     */
    public function download($id)
    {
        Log::info('=== DOWNLOAD REQUEST START ===', ['file_id' => $id]);
        
        $user = Auth::user();

        if (!$user->company_id) {
            Log::error('User not associated with company for download', ['user_id' => $user->id]);
            abort(403, 'Seu usuário não está associado a uma empresa.');
        }

        try {
            // Buscar arquivo do BSDrive
            Log::info('Fetching file from BSDrive', ['file_id' => $id]);
            $file = $this->googleDriveService->getFile($id);

            if (!$file) {
                Log::error('File not found in BSDrive', ['file_id' => $id]);
                abort(404, 'Arquivo não encontrado no BSDrive.');
            }

            Log::info('File found in BSDrive', [
                'file_id' => $id,
                'file_name' => $file->getName(),
                'mime_type' => $file->getMimeType(),
                'parents' => $file->getParents()
            ]);

            // Verificar se o usuário tem acesso ao arquivo
            $fileParents = $file->getParents() ? $file->getParents() : [];
            $hasAccess = false;

            Log::info('Checking user access to file parents', [
                'user_id' => $user->id,
                'file_parents' => $fileParents
            ]);

            foreach ($fileParents as $parentId) {
                if ($user->canAccessCompanyFolder($parentId)) {
                    $hasAccess = true;
                    Log::info('User has access to parent folder', ['parent_id' => $parentId]);
                    break;
                }
            }

            if (!$hasAccess) {
                Log::error('User does not have access to file', [
                    'user_id' => $user->id,
                    'file_id' => $id,
                    'file_parents' => $fileParents
                ]);
                abort(403, 'Você não tem permissão para baixar este arquivo.');
            }

            Log::info('Getting download link from BSDrive Service');
            $downloadLink = $this->googleDriveService->getDownloadLink($id);
            
            Log::info('Download link generated', [
                'file_id' => $id,
                'download_link' => $downloadLink
            ]);

            Log::info('=== DOWNLOAD REQUEST SUCCESS - REDIRECTING ===');
            return redirect($downloadLink);
        } catch (\Exception $e) {
            Log::error('Error downloading file from BSDrive', [
                'file_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Log::info('=== DOWNLOAD REQUEST END WITH ERROR ===');
            abort(500, 'Erro ao baixar arquivo do BSDrive: ' . $e->getMessage());
        }
    }



    /**
     * Preview the specified file from BSDrive (for images and PDFs).
     */
    public function preview($id)
    {
        $user = Auth::user();

        if (!$user->company_id) {
            abort(403, 'Seu usuário não está associado a uma empresa.');
        }

        try {
            $file = $this->googleDriveService->getFile($id);

            if (!$file) {
                abort(404, 'Arquivo não encontrado no BSDrive.');
            }

            // Verificar se o usuário tem acesso ao arquivo
            $fileParents = $file->getParents() ? $file->getParents() : [];
            $hasAccess = false;

            foreach ($fileParents as $parentId) {
                if ($user->canAccessCompanyFolder($parentId)) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                abort(403, 'Você não tem permissão para visualizar este arquivo.');
            }

            $mimeType = $file->getMimeType();

            // Permitir visualização inline para imagens e PDFs
            $isImage = strpos($mimeType, 'image/') === 0;
            $isPdf = $mimeType === 'application/pdf';

            if (!$isImage && !$isPdf) {
                abort(400, 'Este arquivo não pode ser visualizado.');
            }

            // Redirecionar para o link de visualização do BSDrive
            $downloadLink = $this->googleDriveService->getDownloadLink($id);
            return redirect($downloadLink);

        } catch (\Exception $e) {
            Log::error('Error previewing file from BSDrive', [
                'file_id' => $id,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Erro ao visualizar arquivo do BSDrive.');
        }
    }

    /**
     * Bulk delete files from BSDrive.
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_ids' => 'required|array|min:1',
            'file_ids.*' => 'string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $user = Auth::user();

        if (!$user->company_id) {
            return back()->withErrors(['company_id' => 'Seu usuário não está associado a uma empresa.']);
        }

        $deletedCount = 0;
        $errors = [];
        $unauthorizedFiles = [];

        foreach ($request->file_ids as $fileId) {
            try {
                // Verificar permissão de administração para cada arquivo
                $file = $this->googleDriveService->getFile($fileId);
                if (!$file) {
                    $errors[] = "Arquivo {$fileId} não encontrado.";
                    continue;
                }

                $fileParents = $file->getParents() ? $file->getParents() : [];
                $hasAdminAccess = false;

                foreach ($fileParents as $parentId) {
                    if ($user->hasFolderAccess($parentId, 'admin')) {
                        $hasAdminAccess = true;
                        break;
                    }
                }

                if (!$hasAdminAccess) {
                    $unauthorizedFiles[] = $file->getName();
                    continue;
                }

                $this->googleDriveService->deleteFile($fileId);
                $deletedCount++;
            } catch (\Exception $e) {
                Log::error('Error deleting file from BSDrive', [
                    'file_id' => $fileId,
                    'error' => $e->getMessage()
                ]);
                $errors[] = "Erro ao deletar arquivo {$fileId}: " . $e->getMessage();
            }
        }

        if (!empty($unauthorizedFiles)) {
            $errors[] = "Você não tem permissão para excluir os seguintes arquivos: " . implode(', ', $unauthorizedFiles);
        }

        if (!empty($errors)) {
            return back()->withErrors(['bulk_delete' => $errors]);
        }

        $message = $deletedCount === 1
            ? '1 arquivo excluído com sucesso do BSDrive!'
            : "{$deletedCount} arquivos excluídos com sucesso do BSDrive!";

        return redirect()->route('files.index')
            ->with('success', $message);
    }



    /**
     * Get file statistics from BSDrive.
     */
    public function statistics()
    {
        $user = Auth::user();

        if (!$user->company_id) {
            return response()->json(['error' => 'Usuário não associado a uma empresa.'], 403);
        }

        try {
            $files = $this->googleDriveService->listFiles(null, 'files(id,name,mimeType,size,createdTime,modifiedTime)');

            // Filtrar apenas arquivos (não pastas)
            $files = array_filter($files, function($file) {
                return isset($file['mimeType']) && $file['mimeType'] !== 'application/vnd.google-apps.folder';
            });
            $files = array_values($files);

            $totalSize = array_sum(array_column($files, 'size'));

            // Contar por tipo
            $images = 0;
            $documents = 0;
            $videos = 0;
            $audio = 0;
            $archives = 0;

            $documentTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
            ];

            $archiveTypes = [
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'application/gzip',
                'application/x-tar',
            ];

            foreach ($files as $file) {
                $mimeType = $file['mimeType'];

                if (strpos($mimeType, 'image/') === 0) {
                    $images++;
                } elseif (in_array($mimeType, $documentTypes)) {
                    $documents++;
                } elseif (strpos($mimeType, 'video/') === 0) {
                    $videos++;
                } elseif (strpos($mimeType, 'audio/') === 0) {
                    $audio++;
                } elseif (in_array($mimeType, $archiveTypes)) {
                    $archives++;
                }
            }

            $stats = [
                'total_files' => count($files),
                'total_size' => $totalSize,
                'by_type' => [
                    'images' => $images,
                    'documents' => $documents,
                    'videos' => $videos,
                    'audio' => $audio,
                    'archives' => $archives,
                ],
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar estatísticas do BSDrive', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Erro ao carregar estatísticas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download the specified file from BSDrive using direct download method.
     */
    public function downloadDirect($id)
    {
        Log::info('=== DIRECT DOWNLOAD REQUEST START ===', ['file_id' => $id]);
        
        $user = Auth::user();

        if (!$user->company_id) {
            Log::error('User not associated with company for direct download', ['user_id' => $user->id]);
            return response()->json(['error' => 'Seu usuário não está associado a uma empresa.'], 403);
        }

        try {
            // Buscar arquivo do BSDrive
            Log::info('Fetching file from BSDrive for direct download', ['file_id' => $id]);
            $file = $this->googleDriveService->getFile($id);

            if (!$file) {
                Log::error('File not found in BSDrive for direct download', ['file_id' => $id]);
                return response()->json(['error' => 'Arquivo não encontrado no BSDrive.'], 404);
            }

            Log::info('File found in BSDrive for direct download', [
                'file_id' => $id,
                'file_name' => $file->getName(),
                'mime_type' => $file->getMimeType(),
                'parents' => $file->getParents()
            ]);

            // Verificar se o usuário tem acesso ao arquivo
            $fileParents = $file->getParents() ? $file->getParents() : [];
            $hasAccess = false;

            foreach ($fileParents as $parentId) {
                if ($user->canAccessCompanyFolder($parentId)) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                Log::error('User does not have access to file for direct download', [
                    'user_id' => $user->id,
                    'file_id' => $id,
                    'file_parents' => $fileParents
                ]);
                return response()->json(['error' => 'Você não tem permissão para baixar este arquivo.'], 403);
            }

            // Para simplificar, vamos usar o webContentLink primeiro
            Log::info('Getting download link from BSDrive Service');
            $downloadLink = $this->googleDriveService->getDownloadLink($id);
            
            if (!$downloadLink) {
                throw new \Exception('Não foi possível obter o link de download');
            }

            Log::info('Download link generated successfully', [
                'file_id' => $id,
                'download_link' => $downloadLink
            ]);

            Log::info('=== DIRECT DOWNLOAD REQUEST SUCCESS - RETURNING LINK ===');
            
            // Retornar JSON com o link para que o JavaScript possa tratar
            return response()->json([
                'success' => true,
                'download_link' => $downloadLink,
                'file_name' => $file->getName(),
                'file_id' => $id
            ]);

        } catch (\Exception $e) {
            Log::error('Error in direct download file from BSDrive', [
                'file_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Log::info('=== DIRECT DOWNLOAD REQUEST END WITH ERROR ===');
            return response()->json(['error' => 'Erro ao baixar arquivo do BSDrive: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Test download functionality - simple endpoint for debugging
     */
    public function testDownload($id)
    {
        Log::info('=== TEST DOWNLOAD ENDPOINT ===', ['file_id' => $id]);
        
        try {
            $user = Auth::user();
            $file = $this->googleDriveService->getFile($id);
            
            if (!$file) {
                return response()->json(['error' => 'File not found'], 404);
            }
            
            $downloadLink = $this->googleDriveService->getDownloadLink($id);
            
            return response()->json([
                'success' => true,
                'file_id' => $id,
                'file_name' => $file->getName(),
                'mime_type' => $file->getMimeType(),
                'download_link' => $downloadLink,
                'user_id' => $user->id,
                'user_company' => $user->company_id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Test download error', [
                'file_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => $e->getMessage(),
                'file_id' => $id
            ], 500);
        }
    }

    /**
     * Check recent upload status and provide detailed information
     */
    public function uploadStatus()
    {
        $user = Auth::user();
        
        // Pegar os últimos logs de upload (últimos 10 minutos)
        $recentLogs = \Illuminate\Support\Facades\File::exists(storage_path('logs/laravel.log')) 
            ? collect(explode("\n", \Illuminate\Support\Facades\File::get(storage_path('logs/laravel.log'))))
                ->filter(function($line) {
                    return strpos($line, 'BSDrive FILE UPLOAD') !== false;
                })
                ->reverse()
                ->take(20)
                ->values()
                ->toArray()
            : [];
        
        return response()->json([
            'user_id' => $user->id,
            'user_company' => $user->company_id,
            'recent_logs_count' => count($recentLogs),
            'recent_logs' => $recentLogs,
            'timestamp' => now()->toDateTimeString(),
            'google_drive_configured' => !empty(config('services.google.service_account_file')),
            'service_account_file_exists' => file_exists(config('services.google.service_account_file')),
            'service_account_path' => config('services.google.service_account_file'),
            'last_upload_attempt' => session('last_upload_attempt', 'Nenhum'),
            'last_upload_result' => session('last_upload_result', 'Nenhum'),
            'last_upload_count' => session('last_upload_count', 0),
            'last_upload_message' => session('last_upload_message', ''),
            'last_upload_errors' => session('last_upload_errors', []),
            'has_success_message' => session()->has('success'),
            'success_message' => session('success', ''),
            'redirect_302_explanation' => 'Status 302 é NORMAL - indica redirecionamento após upload bem-sucedido'
        ]);
    }

    /**
     * Método de teste para simular upload
     */
    public function testStore(Request $request)
    {
        \Log::emergency('=== TESTE STORE MÉTODO CHAMADO ===');
        \Log::emergency('Dados recebidos: ' . json_encode($request->all()));
        \Log::emergency('Arquivos recebidos: ' . ($request->hasFile('files') ? 'SIM' : 'NÃO'));
        
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                \Log::emergency('Arquivo: ' . $file->getClientOriginalName() . ' - Tamanho: ' . $file->getSize());
            }
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Teste executado com sucesso',
            'files_received' => $request->hasFile('files'),
            'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
            'request_data' => $request->all()
        ]);
    }

    /**
     * Detecta o MIME type de um arquivo de forma segura
     */
    private function detectMimeType($file)
    {
        // Tenta usar o método nativo primeiro
        try {
            return $file->getMimeType();
        } catch (\Exception $e) {
            \Log::emergency('Falha ao detectar MIME type nativo: ' . $e->getMessage());
        }

        // Fallback: detectar por extensão
        $extension = strtolower($file->getClientOriginalExtension());
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
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript'
        ];

        if (isset($mimeTypes[$extension])) {
            \Log::emergency('MIME type detectado por extensão: ' . $extension . ' -> ' . $mimeTypes[$extension]);
            return $mimeTypes[$extension];
        }

        // Default para arquivos desconhecidos
        \Log::emergency('MIME type desconhecido, usando default: application/octet-stream');
        return 'application/octet-stream';
    }

    /**
     * Formatar mensagem de erro de forma mais amigável
     */
    private function formatErrorMessage(\Exception $e, $fileName)
    {
        $message = $e->getMessage();
        
        // Erro de quota de storage
        if (strpos($message, 'storageQuotaExceeded') !== false || strpos($message, 'Service Accounts do not have storage quota') !== false) {
            return "❌ {$fileName}: Erro de quota do BSDrive. O sistema precisa usar Shared Drives. Entre em contato com o administrador.";
        }
        
        // Erro de permissão
        if (strpos($message, '403') !== false || strpos($message, 'Forbidden') !== false) {
            return "🚫 {$fileName}: Sem permissão para enviar arquivo. Verifique as configurações de compartilhamento.";
        }
        
        // Erro de autenticação
        if (strpos($message, '401') !== false || strpos($message, 'Unauthorized') !== false) {
            return "🔑 {$fileName}: Erro de autenticação. Verifique as credenciais do BSDrive.";
        }
        
        // Erro de pasta não encontrada
        if (strpos($message, '404') !== false || strpos($message, 'Not Found') !== false) {
            return "📁 {$fileName}: Pasta de destino não encontrada. ID da pasta pode estar incorreto.";
        }
        
        // Erro de arquivo muito grande
        if (strpos($message, 'file size') !== false || strpos($message, 'too large') !== false) {
            return "📏 {$fileName}: Arquivo muito grande. Limite máximo excedido.";
        }
        
        // Erro de tipo de arquivo
        if (strpos($message, 'file type') !== false || strpos($message, 'mime') !== false) {
            return "📄 {$fileName}: Tipo de arquivo não permitido ou não reconhecido.";
        }
        
        // Erro de conexão
        if (strpos($message, 'connection') !== false || strpos($message, 'timeout') !== false) {
            return "🌐 {$fileName}: Erro de conexão com o BSDrive. Tente novamente.";
        }
        
        // Shared Drive não configurado
        if (strpos($message, 'Shared Drive não configurado') !== false) {
            return "⚙️ {$fileName}: Sistema não configurado. Shared Drive necessário mas não configurado.";
        }
        
        // Erro genérico com detalhes
        return "⚠️ {$fileName}: {$message}";
    }
}
    /**
     * Detecta o MIME type de um arquivo de forma segura
     */
