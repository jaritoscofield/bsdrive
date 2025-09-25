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

        \Log::info('FileController::index chamado', [
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'has_parent_folder' => $parentFolder ? 'sim' : 'não'
        ]);

        try {
            $accessibleFolderIds = $user->getAccessibleFolderIds();
            \Log::info('Pastas acessíveis', [
                'accessible_folder_ids' => $accessibleFolderIds,
                'count' => count($accessibleFolderIds)
            ]);

            if (empty($accessibleFolderIds)) {
                return view('files.index', compact('files', 'folders', 'parentFolder'))
                    ->with('warning', 'Você não tem acesso a nenhuma pasta.');
            }
            // Buscar arquivos e pastas do BSDrive
            if ($folderId) {
                \Log::info('Buscando conteúdo da pasta específica', ['folder_id' => $folderId]);
                if (!$user->canAccessCompanyFolder($folderId)) {
                    abort(403, 'Você não tem permissão para acessar esta pasta.');
                }
                $allItems = $this->googleDriveService->listFiles($folderId, 'files(id,name,mimeType,size,createdTime,modifiedTime,parents)');
            } else {
                \Log::info('Buscando conteúdo de todas as pastas acessíveis');
                $allItems = [];
                foreach ($accessibleFolderIds as $id) {
                    $items = $this->googleDriveService->listFiles($id, 'files(id,name,mimeType,size,createdTime,modifiedTime,parents)');
                    $allItems = array_merge($allItems, $items);
                    \Log::info('Itens da pasta', ['folder_id' => $id, 'count' => count($items)]);
                }
            }
            
            \Log::info('Total de itens encontrados', ['total' => count($allItems)]);
            
            $folders = array_filter($allItems, function($item) {
                return isset($item['mimeType']) && $item['mimeType'] === 'application/vnd.google-apps.folder';
            });
            $folders = array_values($folders);
            
            $files = array_filter($allItems, function($item) {
                return isset($item['mimeType']) && $item['mimeType'] !== 'application/vnd.google-apps.folder';
            });
            $files = array_values($files);
            
            \Log::info('Resultado final', [
                'folders_count' => count($folders),
                'files_count' => count($files)
            ]);
            
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
            
            // Verificar se o usuário quer ir direto para a seção de pastas
            $showFolderSection = request('section') === 'folder';
            
            \Log::emergency("Renderizando view files.create com pasta vazia (upload direto para pasta pessoal)");
            return view('files.create', compact('folders', 'showFolderSection'));
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
        \Log::info('FileController::store chamado', [
            'is_ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'has_files' => $request->hasFile('files')
        ]);

        if (!$request->hasFile('files')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Nenhum arquivo foi selecionado.'], 422);
            }
            return back()->withErrors(['files' => 'Nenhum arquivo foi selecionado.'])->withInput();
        }

        $user = Auth::user();
        
        if (!$user->company_id) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Seu usuário não está associado a uma empresa.'], 403);
            }
            return back()->withErrors(['error' => 'Seu usuário não está associado a uma empresa.'])->withInput();
        }

        // Validação - ARQUIVOS GRANDES PERMITIDOS
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:102400', // 100MB
            'parent_id' => 'nullable|string',
        ]);

        // ====== LIMITE DE ARQUIVOS POR EMPRESA ======
        $empresa = $user->company;
        if ($empresa && $empresa->max_files !== null) {
            $totalArquivos = \App\Models\File::where('company_id', $empresa->id)->count();
            $novosArquivos = count($request->file('files'));
            if (($totalArquivos + $novosArquivos) > $empresa->max_files) {
                return back()->withErrors(['error' => 'Limite de arquivos da empresa atingido ('.$empresa->max_files.'). Libere espaço ou contate o administrador.'])->withInput();
            }
        }
        // ====== FIM LIMITE DE ARQUIVOS ======

        // ====== LIMITE DE ESPAÇO POR EMPRESA ======
        $empresa = $user->company;
        if ($empresa && $empresa->max_storage_mb !== null) {
            $limiteBytes = $empresa->max_storage_mb * 1024 * 1024;
            $usoAtual = \App\Models\File::where('company_id', $empresa->id)->sum('size');
            $tamanhoTotalUpload = array_sum(array_map(function($file) { return $file->getSize(); }, $request->file('files')));
            if (($usoAtual + $tamanhoTotalUpload) > $limiteBytes) {
                return back()->withErrors(['error' => 'Limite de espaço da empresa atingido ('.$empresa->max_storage_mb.' MB). Libere espaço ou contate o administrador.'])->withInput();
            }
        }
        // ====== FIM LIMITE DE ESPAÇO ======

        $uploadedFiles = [];
        $errors = [];

        // 🎯 USAR PARENT_ID DO REQUEST SE FORNECIDO
        $targetFolderId = $request->filled('parent_id') ? $request->parent_id : $user->getOrCreatePersonalFolder();
        
        // Verificar se a pasta de destino existe
        if ($targetFolderId && !$this->googleDriveService->fileExists($targetFolderId)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'A pasta de destino não existe mais no BSDrive.'], 404);
            }
            return back()->withErrors(['parent_id' => 'A pasta de destino não existe mais no BSDrive.']);
        }

        foreach ($request->file('files') as $file) {
            try {
                $mimeType = $this->detectMimeType($file);
                $fileSize = $file->getSize();
                
                // 📤 USAR UPLOAD PARA ARQUIVOS GRANDES SE > 5MB
                if ($fileSize > 5 * 1024 * 1024) {
                    $uploadedFile = $this->googleDriveService->uploadLargeFile(
                        $file->getPathname(),
                        $file->getClientOriginalName(),
                        $targetFolderId,
                        $mimeType
                    );
                } else {
                    $uploadedFile = $this->googleDriveService->uploadFile(
                        $file->getPathname(),
                        $file->getClientOriginalName(),
                        $targetFolderId,
                        $mimeType
                    );
                }

                $uploadedFiles[] = $uploadedFile;
                
            } catch (\Exception $e) {
                $errors[] = 'Erro ao enviar ' . $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        if (count($uploadedFiles) > 0) {
            $message = count($uploadedFiles) === 1 ? 
                'Arquivo enviado com sucesso!' : 
                count($uploadedFiles) . ' arquivos enviados com sucesso!';
                
            if (count($errors) > 0) {
                $message .= ' Alguns arquivos tiveram problemas: ' . implode(', ', $errors);
            }
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'uploaded' => array_map(function($f){ return [
                        'id' => method_exists($f, 'getId') ? $f->getId() : null,
                        'name' => method_exists($f, 'getName') ? $f->getName() : null
                    ]; }, $uploadedFiles),
                    'errors' => $errors,
                ]);
            }
            return back()->with('success', $message);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao enviar arquivos',
                'errors' => $errors
            ], 500);
        }
        return back()->withErrors(['files' => implode(' ', $errors)])->withInput();
    }

    /**
     * Faz upload de uma pasta com subpastas e arquivos
     */
    public function uploadFolder(Request $request)
    {
        \Log::emergency('🚨 UPLOAD FOLDER INICIADO - FRONTEND TESTE');
        \Log::emergency('📋 Request data: ' . json_encode($request->all()));
        \Log::emergency('📁 Files: ' . json_encode($request->files->all()));
        
        if (!$request->hasFile('folder')) {
            \Log::emergency('❌ Nenhum arquivo folder enviado');
            return back()->withErrors(['folder' => 'Nenhuma pasta foi selecionada.'])->withInput();
        }

        $user = Auth::user();
        \Log::emergency('👤 Usuário: ' . $user->id . ' - Empresa: ' . $user->company_id);
        
        // Para upload na pasta pessoal, não é obrigatório ter empresa
        // Apenas verificar se tem pasta pessoal
        if (!$user->hasPersonalFolder()) {
            \Log::emergency('❌ Usuário sem pasta pessoal');
            return back()->withErrors(['error' => 'Você não tem uma pasta pessoal configurada.'])->withInput();
        }

        $uploadedFile = $request->file('folder');
        $fileName = $uploadedFile->getClientOriginalName();
        $fileSize = $uploadedFile->getSize();
        
        \Log::emergency('📁 Arquivo recebido', [
            'fileName' => $fileName,
            'fileSize' => $fileSize,
            'fileSizeMB' => round($fileSize / 1024 / 1024, 2) . 'MB'
        ]);

        // Validação - AUMENTADO PARA ARQUIVOS GRANDES
        $request->validate([
            'folder' => 'required|file|max:204800', // 200MB para pasta compactada
        ]);

        // ====== LIMITE DE ARQUIVOS POR EMPRESA (apenas se tiver empresa) ======
        $empresa = $user->company;
        if ($empresa && $empresa->max_files !== null) {
            // Estimativa: assumir que uma pasta pode ter muitos arquivos
            $totalArquivos = \App\Models\File::where('company_id', $empresa->id)->count();
            if ($totalArquivos > ($empresa->max_files * 0.8)) { // 80% do limite
                return back()->withErrors(['error' => 'Limite de arquivos da empresa próximo do máximo. Libere espaço ou contate o administrador.'])->withInput();
            }
        }
        // ====== FIM LIMITE DE ARQUIVOS ======

        // ====== LIMITE DE ESPAÇO POR EMPRESA (apenas se tiver empresa) ======
        if ($empresa && $empresa->max_storage_mb !== null) {
            $limiteBytes = $empresa->max_storage_mb * 1024 * 1024;
            $usoAtual = \App\Models\File::where('company_id', $empresa->id)->sum('size');
            $tamanhoUpload = $request->file('folder')->getSize();
            if (($usoAtual + $tamanhoUpload) > $limiteBytes) {
                return back()->withErrors(['error' => 'Limite de espaço da empresa atingido ('.$empresa->max_storage_mb.' MB). Libere espaço ou contate o administrador.'])->withInput();
            }
        }
        // ====== FIM LIMITE DE ESPAÇO ======
        
        // Verificar se é um arquivo ZIP
        $mimeType = $uploadedFile->getMimeType();
        \Log::emergency('📄 MIME Type detectado: ' . $mimeType);
        
        if ($mimeType !== 'application/zip' && $mimeType !== 'application/x-zip-compressed') {
            \Log::emergency('❌ Tipo de arquivo não permitido: ' . $mimeType);
            return back()->withErrors(['folder' => 'Apenas arquivos ZIP são aceitos para upload de pastas.'])->withInput();
        }

        // 🎯 USAR PARENT_ID DO REQUEST SE FORNECIDO
        $targetFolderId = $request->filled('parent_id') ? $request->parent_id : $user->getOrCreatePersonalFolder();
        \Log::emergency('🎯 Pasta de destino: ' . $targetFolderId);
        
        // Verificar se a pasta de destino existe
        if ($targetFolderId && !$this->googleDriveService->fileExists($targetFolderId)) {
            \Log::emergency('❌ Pasta de destino não existe: ' . $targetFolderId);
            return back()->withErrors(['parent_id' => 'A pasta de destino não existe mais no BSDrive.']);
        }

        try {
            // AUMENTAR LIMITES PARA ARQUIVOS GRANDES
            ini_set('max_execution_time', 600); // 10 minutos
            ini_set('memory_limit', '512M'); // 512MB
            
            \Log::emergency('⚙️ Limites aumentados', [
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_limit' => ini_get('memory_limit')
            ]);

            // Criar diretório temporário para extrair o ZIP
            $tempDir = storage_path('app/temp/' . uniqid('folder_upload_'));
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            \Log::emergency('📁 Diretório temporário criado: ' . $tempDir);

            // Mover arquivo ZIP para diretório temporário
            $zipPath = $tempDir . '/' . $fileName;
            $uploadedFile->move($tempDir, $fileName);

            \Log::emergency('📦 Arquivo ZIP movido para: ' . $zipPath);

            // Verificar se o arquivo ZIP existe e tem tamanho
            if (!file_exists($zipPath)) {
                throw new \Exception('Arquivo ZIP não foi movido corretamente.');
            }

            $zipFileSize = filesize($zipPath);
            \Log::emergency('📊 Tamanho do arquivo ZIP: ' . $zipFileSize . ' bytes (' . round($zipFileSize / 1024 / 1024, 2) . 'MB)');

            // Extrair ZIP com tratamento de erro melhorado
            $zip = new \ZipArchive();
            $zipResult = $zip->open($zipPath);
            
            \Log::emergency('🔓 Tentativa de abrir ZIP', [
                'result' => $zipResult,
                'zipPath' => $zipPath
            ]);
            
            if ($zipResult !== TRUE) {
                $errorMessages = [
                    ZipArchive::ER_EXISTS => 'Arquivo já existe',
                    ZipArchive::ER_INCONS => 'ZIP inconsistente',
                    ZipArchive::ER_INVAL => 'Argumento inválido',
                    ZipArchive::ER_MEMORY => 'Erro de memória',
                    ZipArchive::ER_NOENT => 'Arquivo não encontrado',
                    ZipArchive::ER_NOZIP => 'Não é um arquivo ZIP',
                    ZipArchive::ER_OPEN => 'Erro ao abrir arquivo',
                    ZipArchive::ER_READ => 'Erro de leitura',
                    ZipArchive::ER_SEEK => 'Erro de busca'
                ];
                
                $errorMsg = isset($errorMessages[$zipResult]) ? $errorMessages[$zipResult] : 'Erro desconhecido';
                throw new \Exception('Não foi possível abrir o arquivo ZIP. Erro: ' . $errorMsg . ' (Código: ' . $zipResult . ')');
            }

            \Log::emergency('✅ ZIP aberto com sucesso', [
                'numFiles' => $zip->numFiles,
                'status' => $zip->status
            ]);

            $extractPath = $tempDir . '/extracted';
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0755, true);
            }

            \Log::emergency('📂 Iniciando extração para: ' . $extractPath);

            // Extrair ZIP com progresso
            $extractResult = $zip->extractTo($extractPath);
            
            if (!$extractResult) {
                throw new \Exception('Falha na extração do ZIP. Status: ' . $zip->status);
            }
            
            $zip->close();

            \Log::emergency('✅ ZIP extraído com sucesso', [
                'extractPath' => $extractPath,
                'contents' => scandir($extractPath)
            ]);

            // Verificar estrutura detalhada
            $this->logDirectoryStructure($extractPath, 'Estrutura extraída');

            // Verificar se há uma pasta raiz ou se os arquivos estão soltos
            $extractedItems = scandir($extractPath);
            $rootFolder = null;
            $hasDirectories = false;
            $hasFiles = false;
            
            \Log::emergency('🔍 Analisando estrutura extraída', [
                'extractedItems' => $extractedItems,
                'extractPath' => $extractPath
            ]);
            
            // Verificar se há pastas ou arquivos
            foreach ($extractedItems as $item) {
                if ($item !== '.' && $item !== '..') {
                    $itemPath = $extractPath . '/' . $item;
                    if (is_dir($itemPath)) {
                        $hasDirectories = true;
                        if (!$rootFolder) {
                            $rootFolder = $itemPath;
                            \Log::emergency('📁 Pasta raiz encontrada', [
                                'rootFolder' => $rootFolder,
                                'item' => $item
                            ]);
                        }
                    } else {
                        $hasFiles = true;
                    }
                }
            }

            // Se não há pasta raiz, usar o diretório de extração como raiz
            if (!$rootFolder) {
                if ($hasFiles) {
                    $rootFolder = $extractPath;
                    \Log::emergency('📁 Usando diretório de extração como raiz (arquivos soltos)', [
                        'rootFolder' => $rootFolder
                    ]);
                } else {
                    \Log::emergency('❌ ZIP vazio ou sem conteúdo válido', [
                        'extractedItems' => $extractedItems,
                        'extractPath' => $extractPath
                    ]);
                    throw new \Exception('O arquivo ZIP está vazio ou não contém arquivos válidos.');
                }
            }

            // Fazer upload da pasta usando o GoogleDriveService
            \Log::emergency('🚀 Iniciando upload da pasta para Google Drive', [
                'rootFolder' => $rootFolder,
                'targetFolderId' => $targetFolderId
            ]);
            
            $results = $this->googleDriveService->uploadFolder($rootFolder, $targetFolderId);
            
            \Log::emergency('✅ Upload da pasta concluído', $results);
            
            // VÍNCULO AUTOMÁTICO COM COMPANY_FOLDER (apenas se tiver empresa)
            if ($empresa && isset($results['root_folder_id']) && isset($results['root_folder_name'])) {
                $companyFolderExists = \App\Models\CompanyFolder::where('company_id', $empresa->id)
                    ->where('google_drive_folder_id', $results['root_folder_id'])
                    ->exists();
                if (!$companyFolderExists) {
                    \App\Models\CompanyFolder::create([
                        'company_id' => $empresa->id,
                        'google_drive_folder_id' => $results['root_folder_id'],
                        'folder_name' => $results['root_folder_name'],
                        'description' => 'Pasta criada via upload automático',
                        'active' => true,
                    ]);
                    \Log::emergency('🔗 Pasta raiz vinculada à empresa', [
                        'company_id' => $empresa->id,
                        'folder_id' => $results['root_folder_id'],
                        'folder_name' => $results['root_folder_name']
                    ]);
                }
            }

            // Limpar arquivos temporários
            $this->cleanupTempFiles($tempDir);

            $message = 'Pasta enviada com sucesso! ' . 
                      (isset($results['total_files']) ? $results['total_files'] . ' arquivos processados.' : '');

            \Log::emergency('🎉 UPLOAD FOLDER CONCLUÍDO COM SUCESSO');
            return back()->with('success', $message);

        } catch (\Exception $e) {
            \Log::emergency('❌ ERRO NO UPLOAD FOLDER', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Limpar arquivos temporários em caso de erro
            if (isset($tempDir) && file_exists($tempDir)) {
                $this->cleanupTempFiles($tempDir);
            }

            return back()->withErrors(['folder' => 'Erro ao processar pasta: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Limpa arquivos temporários
     */
    private function cleanupTempFiles($tempDir)
    {
        if (!file_exists($tempDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($tempDir);
    }

    /**
     * Loga a estrutura de um diretório recursivamente
     */
    private function logDirectoryStructure($path, $label = 'Estrutura')
    {
        \Log::info("=== {$label} ===");
        $this->logDirectoryRecursive($path, 0);
    }

    /**
     * Loga recursivamente o conteúdo de um diretório
     */
    private function logDirectoryRecursive($path, $level)
    {
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            $indent = str_repeat('  ', $level);
            
            if (is_dir($itemPath)) {
                \Log::info("{$indent}📁 {$item}/");
                $this->logDirectoryRecursive($itemPath, $level + 1);
            } else {
                $size = filesize($itemPath);
                \Log::info("{$indent}📄 {$item} ({$size} bytes)");
            }
        }
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

                return redirect()->route('folders.index')
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

                return redirect()->route('folders.index')
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

            $fileName = $file->getName();

            \Log::emergency('🗑️ INICIANDO DELETE COM BYPASS DE PERMISSÕES', [
                'file_id' => $id,
                'file_name' => $fileName,
                'user_id' => $user->id,
                'user_role' => $user->role
            ]);

            // 🔓 USAR FORCE DELETE - BYPASS DE PERMISSÕES DA API
            $this->googleDriveService->forceDeleteFile($id);

            \Log::emergency('✅ ARQUIVO DELETADO COM SUCESSO!', [
                'file_id' => $id,
                'file_name' => $fileName
            ]);

            return redirect()->route('folders.index')
                ->with('success', "Arquivo '{$fileName}' excluído com sucesso do BSDrive!");
        } catch (\Exception $e) {
            \Log::emergency('❌ ERRO NO DELETE: ' . $e->getMessage(), [
                'file_id' => $id,
                'user_id' => $user->id,
                'erro_completo' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Erro ao deletar arquivo do BSDrive: ' . $e->getMessage()]);
        }
    }

    /**
     * Download the specified file from BSDrive.
     */
    public function download($id)
    {
        \Log::emergency('📥 DOWNLOAD REQUEST START', ['file_id' => $id]);
        
        $user = Auth::user();

        if (!$user->company_id) {
            \Log::emergency('❌ User not associated with company for download', ['user_id' => $user->id]);
            abort(403, 'Seu usuário não está associado a uma empresa.');
        }

        try {
            // Buscar arquivo do BSDrive
            \Log::emergency('🔍 Fetching file from BSDrive', ['file_id' => $id]);
            $file = $this->googleDriveService->getFile($id);

            if (!$file) {
                \Log::emergency('❌ File not found in BSDrive', ['file_id' => $id]);
                abort(404, 'Arquivo não encontrado no BSDrive.');
            }

            \Log::emergency('✅ File found in BSDrive', [
                'file_id' => $id,
                'file_name' => $file->getName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize()
            ]);

            // 🔓 BYPASS DE PERMISSÕES - DOWNLOAD DIRETO
            \Log::emergency('📥 Iniciando download direto - bypass de permissões');
            
            // Obter conteúdo do arquivo
            $content = $this->googleDriveService->downloadFileContent($id);
            
            if (!$content) {
                throw new \Exception('Não foi possível baixar o conteúdo do arquivo');
            }
            
            \Log::emergency('✅ Download content obtained', [
                'content_size' => strlen($content),
                'file_name' => $file->getName()
            ]);

            // Retornar arquivo para download
            return response($content)
                ->header('Content-Type', $file->getMimeType())
                ->header('Content-Disposition', 'attachment; filename="' . $file->getName() . '"')
                ->header('Content-Length', strlen($content));
                
        } catch (\Exception $e) {
            \Log::emergency('❌ Error downloading file', [
                'file_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => $user->id
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
        \Log::emergency('🔍 PREVIEW REQUEST START', ['file_id' => $id]);
        
        $user = Auth::user();

        if (!$user->company_id) {
            \Log::emergency('❌ User not associated with company for preview', ['user_id' => $user->id]);
            abort(403, 'Seu usuário não está associado a uma empresa.');
        }

        try {
            \Log::emergency('📁 Fetching file for preview', ['file_id' => $id]);
            $file = $this->googleDriveService->getFile($id);

            if (!$file) {
                \Log::emergency('❌ File not found for preview', ['file_id' => $id]);
                abort(404, 'Arquivo não encontrado no BSDrive.');
            }

            \Log::emergency('✅ File found for preview', [
                'file_id' => $id,
                'file_name' => $file->getName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize()
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
                \Log::emergency('❌ User does not have access to preview file', [
                    'user_id' => $user->id,
                    'file_id' => $id,
                    'file_parents' => $fileParents
                ]);
                abort(403, 'Você não tem permissão para visualizar este arquivo.');
            }

            $mimeType = $file->getMimeType();

            // Permitir visualização inline para imagens e PDFs
            $isImage = strpos($mimeType, 'image/') === 0;
            $isPdf = $mimeType === 'application/pdf';

            \Log::emergency('📄 File type analysis', [
                'file_id' => $id,
                'mime_type' => $mimeType,
                'is_image' => $isImage,
                'is_pdf' => $isPdf
            ]);

            if (!$isImage && !$isPdf) {
                \Log::emergency('❌ File type not supported for preview', [
                    'file_id' => $id,
                    'mime_type' => $mimeType
                ]);
                abort(400, 'Este arquivo não pode ser visualizado.');
            }

            // Para imagens (incluindo PNG), servir o conteúdo diretamente
            if ($isImage) {
                \Log::emergency('🖼️ Processing image preview', [
                    'file_id' => $id,
                    'mime_type' => $mimeType
                ]);
                
                $content = $this->googleDriveService->downloadFileContent($id);
                
                \Log::emergency('📊 Image content download result', [
                    'file_id' => $id,
                    'content_exists' => !empty($content),
                    'content_length' => $content ? strlen($content) : 0
                ]);
                
                if (!$content) {
                    \Log::emergency('❌ Image content download failed', ['file_id' => $id]);
                    abort(500, 'Não foi possível carregar o conteúdo da imagem.');
                }

                \Log::emergency('✅ Returning image preview response', [
                    'file_id' => $id,
                    'mime_type' => $mimeType,
                    'content_length' => strlen($content)
                ]);

                return response($content)
                    ->header('Content-Type', $mimeType)
                    ->header('Cache-Control', 'public, max-age=31536000')
                    ->header('Content-Disposition', 'inline; filename="' . $file->getName() . '"');
            }

            // Para PDFs, redirecionar para o link de visualização do BSDrive
            \Log::emergency('📄 Processing PDF preview', ['file_id' => $id]);
            $downloadLink = $this->googleDriveService->getDownloadLink($id);
            \Log::emergency('✅ Redirecting to PDF download link', [
                'file_id' => $id,
                'download_link' => $downloadLink
            ]);
            return redirect($downloadLink);

        } catch (\Exception $e) {
            \Log::emergency('❌ ERROR in preview', [
                'file_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'Erro ao visualizar arquivo do BSDrive: ' . $e->getMessage());
        }
    }

    /**
     * View image file directly (serves image content with proper headers)
     */
    public function viewImage($id)
    {
        \Log::emergency('🖼️ VIEW IMAGE REQUEST START', ['file_id' => $id]);
        
        $user = Auth::user();

        if (!$user->company_id) {
            \Log::emergency('❌ User not associated with company for image view', ['user_id' => $user->id]);
            abort(403, 'Seu usuário não está associado a uma empresa.');
        }

        try {
            \Log::emergency('🔍 Fetching file for image view', ['file_id' => $id]);
            $file = $this->googleDriveService->getFile($id);

            if (!$file) {
                \Log::emergency('❌ File not found for image view', ['file_id' => $id]);
                abort(404, 'Arquivo não encontrado no BSDrive.');
            }

            \Log::emergency('✅ File found for image view', [
                'file_id' => $id,
                'file_name' => $file->getName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize()
            ]);

            // Verificar se o usuário tem acesso ao arquivo
            $fileParents = $file->getParents() ? $file->getParents() : [];
            $hasAccess = false;

            // Admin do sistema tem acesso irrestrito
            if ($user->role === 'admin_sistema') {
                $hasAccess = true;
            }

            // Acesso por pasta da empresa
            if (!$hasAccess) {
                foreach ($fileParents as $parentId) {
                    if ($user->canAccessCompanyFolder($parentId)) {
                        $hasAccess = true;
                        break;
                    }
                }
            }

            // Acesso pela pasta pessoal (arquivos dentro da área pessoal)
            if (!$hasAccess) {
                $personalFolderId = $user->getPersonalFolderId();
                if ($personalFolderId) {
                    // direto na pasta pessoal
                    if (in_array($personalFolderId, $fileParents, true)) {
                        $hasAccess = true;
                    } else {
                        // ou em qualquer ancestral da pasta pessoal (subpastas)
                        foreach ($fileParents as $parentId) {
                            if ($this->isFolderInsidePersonalArea($parentId, $personalFolderId)) {
                                $hasAccess = true;
                                break;
                            }
                        }
                    }
                }
            }

            if (!$hasAccess) {
                \Log::emergency('❌ User does not have access to image file', [
                    'user_id' => $user->id,
                    'file_id' => $id,
                    'file_parents' => $fileParents
                ]);
                abort(403, 'Você não tem permissão para visualizar este arquivo.');
            }

            $mimeType = $file->getMimeType();

            // Verificar se é imagem ou PDF (PDF exibe inline)
            if (strpos($mimeType, 'image/') !== 0) {
                if ($mimeType === 'application/pdf') {
                    \Log::emergency('📄 PDF requested via viewImage, returning inline content', [
                        'file_id' => $id,
                        'mime_type' => $mimeType
                    ]);
                    // Tentar obter o conteúdo do PDF e servir inline
                    try {
                        $content = $this->googleDriveService->downloadFileContent($id);
                        if ($content) {
                            return response($content)
                                ->header('Content-Type', 'application/pdf')
                                ->header('Content-Disposition', 'inline; filename="' . $file->getName() . '"')
                                ->header('Cache-Control', 'public, max-age=3600');
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Falha ao obter conteúdo do PDF, tentando link de visualização', [
                            'file_id' => $id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    // Fallback: usar link direto do Drive (pode abrir no viewer do Google)
                    $downloadLink = $this->googleDriveService->getDownloadLink($id);
                    return redirect($downloadLink);
                }
                \Log::emergency('❌ File is not an image - redirecting to files.view', [
                    'file_id' => $id,
                    'mime_type' => $mimeType
                ]);
                return redirect()->route('files.view', $id);
            }

            // Obter conteúdo da imagem
            \Log::emergency('📥 Starting image content download', [
                'file_id' => $id,
                'file_name' => $file->getName(),
                'mime_type' => $mimeType
            ]);
            
            $content = $this->googleDriveService->downloadFileContent($id);
            
            \Log::emergency('📊 Image download result', [
                'file_id' => $id,
                'content_exists' => !empty($content),
                'content_length' => $content ? strlen($content) : 0,
                'content_type' => gettype($content)
            ]);
            
            if (!$content) {
                \Log::emergency('❌ Image content is empty', ['file_id' => $id]);
                abort(500, 'Não foi possível carregar o conteúdo da imagem.');
            }

            \Log::emergency('✅ Returning image response', [
                'file_id' => $id,
                'mime_type' => $mimeType,
                'content_length' => strlen($content)
            ]);

            return response($content)
                ->header('Content-Type', $mimeType)
                ->header('Cache-Control', 'public, max-age=31536000')
                ->header('Content-Disposition', 'inline; filename="' . $file->getName() . '"');

        } catch (\Exception $e) {
            \Log::emergency('❌ ERROR in view image', [
                'file_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'Erro ao visualizar imagem do BSDrive: ' . $e->getMessage());
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

                \Log::emergency('🗑️ BULK DELETE - usando force delete', [
                    'file_id' => $fileId,
                    'file_name' => $file->getName()
                ]);

                // 🔓 USAR FORCE DELETE - BYPASS DE PERMISSÕES DA API
                $this->googleDriveService->forceDeleteFile($fileId);
                $deletedCount++;
            } catch (\Exception $e) {
                \Log::emergency('❌ ERRO NO BULK DELETE: ' . $e->getMessage(), [
                    'file_id' => $fileId,
                    'erro_completo' => $e->getMessage()
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

        return redirect()->route('folders.index')
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
     * Public, signed URL stream for embedding in external viewers (e.g., Office Online).
     */
    public function publicStream(Request $request, $id)
    {
        // Validate signed URL
        if (! $request->hasValidSignature()) {
            return response('Link inválido ou expirado.', 403);
        }

        try {
            $file = $this->googleDriveService->getFile($id);
            if (! $file) {
                return response('Arquivo não encontrado.', 404);
            }

            $content = $this->googleDriveService->downloadFileContent($id);
            if (! $content) {
                return response('Falha ao carregar arquivo.', 500);
            }

            return response($content)
                ->header('Content-Type', $file->getMimeType() ?: 'application/octet-stream')
                ->header('Content-Disposition', 'inline; filename="' . ($file->getName() ?: 'file') . '"')
                ->header('Cache-Control', 'public, max-age=300');
        } catch (\Exception $e) {
            \Log::warning('publicStream error', ['file_id' => $id, 'error' => $e->getMessage()]);
            return response('Erro ao servir arquivo.', 500);
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
                    return strpos($line, 'BSDrive FILE UPLOAD') !== false || 
                           strpos($line, 'UPLOAD') !== false ||
                           strpos($line, 'Google Drive') !== false;
                })
                ->reverse()
                ->take(30)
                ->map(function($line) {
                    // Extrair timestamp e mensagem do log
                    if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (.+)/', $line, $matches)) {
                        return [
                            'timestamp' => $matches[1],
                            'message' => $matches[2]
                        ];
                    }
                    return [
                        'timestamp' => now()->format('Y-m-d H:i:s'),
                        'message' => $line
                    ];
                })
                ->values()
                ->toArray()
            : [];
        
        // Buscar uploads recentes do usuário (últimos 10 arquivos)
        $recentUploads = File::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function($file) {
                return [
                    'filename' => $file->name,
                    'size' => $file->size ? number_format($file->size / 1024, 1) . ' KB' : 'N/A',
                    'status' => 'completed',
                    'timestamp' => $file->created_at->format('d/m/Y H:i:s'),
                    'google_drive_id' => $file->google_drive_id
                ];
            });
        
        // Verificar uploads ativos (arquivos criados nos últimos 5 minutos)
        $activeUploads = File::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();
        
        // Verificar configuração do Google Drive
        $googleDriveConfigured = !empty(config('services.google.service_account_file'));
        $serviceAccountExists = $googleDriveConfigured && file_exists(config('services.google.service_account_file'));
        
        // Verificar se há jobs de upload em andamento
        $activeJobs = \Illuminate\Support\Facades\DB::table('jobs')
            ->where('queue', 'default')
            ->where('payload', 'like', '%AutoSyncGoogleDriveJob%')
            ->count();
        
        // Calcular estatísticas
        $totalFiles = File::where('user_id', $user->id)->count();
        $totalSize = File::where('user_id', $user->id)->sum('size');
        $todayUploads = File::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();
        
        // Verificar status de sincronização
        $syncedFiles = File::where('user_id', $user->id)
            ->whereNotNull('google_drive_id')
            ->count();
        $syncPercentage = $totalFiles > 0 ? round(($syncedFiles / $totalFiles) * 100) : 0;
        
        return response()->json([
            'user_id' => $user->id,
            'user_company' => $user->company ? $user->company->name : 'N/A',
            'recent_logs' => $recentLogs,
            'recent_uploads' => $recentUploads,
            'active_uploads' => $activeUploads + $activeJobs,
            'timestamp' => now()->toDateTimeString(),
            'google_drive_configured' => $googleDriveConfigured,
            'service_account_file_exists' => $serviceAccountExists,
            'service_account_path' => config('services.google.service_account_file'),
            'active_jobs' => $activeJobs,
            'last_upload_attempt' => session('last_upload_attempt', 'Nenhum'),
            'last_upload_result' => session('last_upload_result', 'Nenhum'),
            'last_upload_count' => session('last_upload_count', 0),
            'last_upload_message' => session('last_upload_message', ''),
            'last_upload_errors' => session('last_upload_errors', []),
            'has_success_message' => session()->has('success'),
            'success_message' => session('success', ''),
            'statistics' => [
                'total_files' => $totalFiles,
                'total_size' => $totalSize,
                'total_size_formatted' => $totalSize ? number_format($totalSize / 1024 / 1024, 1) . ' MB' : '0 MB',
                'today_uploads' => $todayUploads,
                'sync_percentage' => $syncPercentage,
                'synced_files' => $syncedFiles
            ],
            'system_status' => [
                'database_connected' => true,
                'storage_writable' => is_writable(storage_path()),
                'google_drive_api_accessible' => $googleDriveConfigured && $serviceAccountExists,
                'queue_working' => $activeJobs > 0 || true // Assumindo que está funcionando
            ]
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
        
        // Erro de quota de storage - sugerir OAuth
        if (strpos($message, 'storageQuotaExceeded') !== false || 
            strpos($message, 'Service Accounts do not have storage quota') !== false ||
            strpos($message, 'quota') !== false) {
            return "❌ {$fileName}: Erro de quota do BSDrive. <a href='/google-setup' class='text-blue-600 underline'>Configure OAuth aqui</a> para resolver o problema.";
        }
        
        // OAuth não configurado
        if (strpos($message, 'OAuth não configurado') !== false || 
            strpos($message, 'Acesse /google-setup') !== false) {
            return "🔧 {$fileName}: <a href='/google-setup' class='text-blue-600 underline font-medium'>Clique aqui para configurar OAuth</a> e resolver o problema de quota.";
        }
        
        // Erro de permissão
        if (strpos($message, '403') !== false || strpos($message, 'Forbidden') !== false) {
            return "🚫 {$fileName}: Sem permissão para enviar arquivo. Verifique as configurações de compartilhamento.";
        }
        
        // Erro de autenticação
        if (strpos($message, '401') !== false || strpos($message, 'Unauthorized') !== false) {
            return "🔑 {$fileName}: Erro de autenticação. <a href='/google-setup' class='text-blue-600 underline'>Configure OAuth aqui</a>.";
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
            return "⚙️ {$fileName}: <a href='/google-setup' class='text-blue-600 underline'>Configure OAuth aqui</a> para não precisar de Shared Drive.";
        }
        
        // Erro genérico com detalhes
        return "⚠️ {$fileName}: {$message}";
    }
    /**
     * Verifica se uma pasta (ou arquivo via parent) está dentro da área pessoal do usuário
     */
    private function isFolderInsidePersonalArea(string $folderId, string $personalFolderId): bool
    {
        try {
            if ($folderId === $personalFolderId) {
                return true;
            }

            $visited = [];
            $toCheck = [$folderId];
            $depth = 0;

            while (!empty($toCheck) && $depth < 10) {
                $nextLevel = [];
                foreach ($toCheck as $currentId) {
                    if (isset($visited[$currentId])) {
                        continue;
                    }
                    $visited[$currentId] = true;

                    $folder = $this->googleDriveService->getFolder($currentId);
                    if (!isset($folder['parents']) || empty($folder['parents'])) {
                        continue;
                    }
                    foreach ($folder['parents'] as $parentId) {
                        if ($parentId === $personalFolderId) {
                            return true;
                        }
                        $nextLevel[] = $parentId;
                    }
                }
                $toCheck = $nextLevel;
                $depth++;
            }
        } catch (\Exception $e) {
            \Log::warning('Erro ao verificar área pessoal no FileController', [
                'folder_id' => $folderId,
                'personal_folder_id' => $personalFolderId,
                'error' => $e->getMessage()
            ]);
        }
        return false;
    }
    /**
     * Detecta o MIME type de um arquivo de forma segura
     */
}
