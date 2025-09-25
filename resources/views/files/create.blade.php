@extends('layouts.dashboard')

@section('title', 'Enviar Arquivos')

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-neutral-900">Enviar Arquivos para Sua Pasta Pessoal</h1>
            <p class="text-neutral-600">Os arquivos serão enviados diretamente para sua pasta pessoal no BSDrive</p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(isset($showFolderSection) && $showFolderSection)
            <!-- Apenas Upload de Pastas -->
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-neutral-900 mb-4">Enviar Pasta com Subpastas</h2>
                        
                        <form action="{{ route('files.upload-folder') }}" method="POST" enctype="multipart/form-data" id="folderUploadForm">
                            @csrf

                            <!-- Folder Upload Area -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-neutral-700 mb-3">
                                    Selecionar Pasta (ZIP)
                                </label>

                                <div class="border-2 border-dashed border-neutral-300 rounded-lg p-6 text-center">
                                    <div class="space-y-3">
                                        <svg class="mx-auto h-10 w-10 text-neutral-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M20 4H6a2 2 0 00-2 2v16a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M14 2v4M22 2v4M10 14h8M10 18h8M10 22h8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-neutral-900">Clique para selecionar pasta ZIP</p>
                                            <p class="text-xs text-neutral-500">Compacte sua pasta em ZIP (máximo 100MB)</p>
                                        </div>
                                        <input type="file" name="folder" id="folderInput" accept=".zip" required class="hidden">
                                        <button type="button" onclick="document.getElementById('folderInput').click()" 
                                                class="inline-flex items-center px-3 py-2 bg-neutral-900 text-white text-sm font-medium rounded-lg hover:bg-neutral-800">
                                            Selecionar Pasta ZIP
                                        </button>
                                    </div>
                                </div>

                                <!-- Selected Folder Info -->
                                <div id="selectedFolder" class="mt-4 hidden">
                                    <h4 class="text-sm font-medium text-neutral-700 mb-2">Pasta Selecionada:</h4>
                                    <div id="folderInfo" class="p-2 bg-neutral-50 rounded text-sm text-neutral-700"></div>
                                </div>
                            </div>

                            <!-- Instructions -->
                            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <h4 class="text-sm font-medium text-blue-900 mb-2">Como preparar sua pasta:</h4>
                                <ul class="text-xs text-blue-800 space-y-1">
                                    <li>• Selecione a pasta que deseja enviar</li>
                                    <li>• Clique com botão direito → "Enviar para" → "Pasta compactada"</li>
                                    <li>• Ou use WinRAR/7-Zip para criar arquivo ZIP</li>
                                    <li>• A estrutura de subpastas será mantida no BSDrive</li>
                                </ul>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button type="submit" id="submitFolderBtn"
                                        class="inline-flex items-center px-4 py-2 bg-neutral-900 text-white text-sm font-medium rounded-lg hover:bg-neutral-800 disabled:opacity-50">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    Enviar Pasta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <!-- Layout original com duas colunas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Upload de Arquivos -->
                <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-neutral-900 mb-4">Enviar Arquivos</h2>
                        
                        <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                            @csrf

                            <!-- File Upload Area -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-neutral-700 mb-3">
                                    Selecionar Arquivos
                                </label>

                                <div class="border-2 border-dashed border-neutral-300 rounded-lg p-6 text-center">
                                    <div class="space-y-3">
                                        <svg class="mx-auto h-10 w-10 text-neutral-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-neutral-900">Clique para selecionar arquivos</p>
                                            <p class="text-xs text-neutral-500">Ou arraste arquivos aqui (máximo 100MB cada)</p>
                                        </div>
                                        <input type="file" name="files[]" id="fileInput" multiple required class="hidden">
                                        <button type="button" onclick="document.getElementById('fileInput').click()" 
                                                class="inline-flex items-center px-3 py-2 bg-neutral-900 text-white text-sm font-medium rounded-lg hover:bg-neutral-800">
                                            Selecionar Arquivos
                                        </button>
                                    </div>
                                </div>

                                <!-- Selected Files List -->
                                <div id="selectedFiles" class="mt-4 hidden">
                                    <h4 class="text-sm font-medium text-neutral-700 mb-2">Arquivos Selecionados:</h4>
                                    <div id="filesList" class="space-y-2"></div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button type="submit" id="submitBtn"
                                        class="inline-flex items-center px-4 py-2 bg-neutral-900 text-white text-sm font-medium rounded-lg hover:bg-neutral-800 disabled:opacity-50">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    Enviar Arquivos
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Upload de Pastas -->
                <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-neutral-900 mb-4">Enviar Pasta com Subpastas</h2>
                        
                        <form action="{{ route('files.upload-folder') }}" method="POST" enctype="multipart/form-data" id="folderUploadForm">
                            @csrf

                            <!-- Folder Upload Area -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-neutral-700 mb-3">
                                    Selecionar Pasta (ZIP)
                                </label>

                                <div class="border-2 border-dashed border-neutral-300 rounded-lg p-6 text-center">
                                    <div class="space-y-3">
                                        <svg class="mx-auto h-10 w-10 text-neutral-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M20 4H6a2 2 0 00-2 2v16a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M14 2v4M22 2v4M10 14h8M10 18h8M10 22h8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-neutral-900">Clique para selecionar pasta ZIP</p>
                                            <p class="text-xs text-neutral-500">Compacte sua pasta em ZIP (máximo 100MB)</p>
                                        </div>
                                        <input type="file" name="folder" id="folderInput" accept=".zip" required class="hidden">
                                        <button type="button" onclick="document.getElementById('folderInput').click()" 
                                                class="inline-flex items-center px-3 py-2 bg-neutral-900 text-white text-sm font-medium rounded-lg hover:bg-neutral-800">
                                            Selecionar Pasta ZIP
                                        </button>
                                    </div>
                                </div>

                                <!-- Selected Folder Info -->
                                <div id="selectedFolder" class="mt-4 hidden">
                                    <h4 class="text-sm font-medium text-neutral-700 mb-2">Pasta Selecionada:</h4>
                                    <div id="folderInfo" class="p-2 bg-neutral-50 rounded text-sm text-neutral-700"></div>
                                </div>
                            </div>

                            <!-- Instructions -->
                            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <h4 class="text-sm font-medium text-blue-900 mb-2">Como preparar sua pasta:</h4>
                                <ul class="text-xs text-blue-800 space-y-1">
                                    <li>• Selecione a pasta que deseja enviar</li>
                                    <li>• Clique com botão direito → "Enviar para" → "Pasta compactada"</li>
                                    <li>• Ou use WinRAR/7-Zip para criar arquivo ZIP</li>
                                    <li>• A estrutura de subpastas será mantida no BSDrive</li>
                                </ul>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button type="submit" id="submitFolderBtn"
                                        class="inline-flex items-center px-4 py-2 bg-neutral-900 text-white text-sm font-medium rounded-lg hover:bg-neutral-800 disabled:opacity-50">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    Enviar Pasta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Cancel Button -->
        <div class="mt-6 flex justify-center">
            <a href="{{ route('files.index') }}" 
               class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50">
                Cancelar
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Script de upload carregado');
    
    // File upload handling (apenas se existir)
    const fileInput = document.getElementById('fileInput');
    const selectedFiles = document.getElementById('selectedFiles');
    const filesList = document.getElementById('filesList');
    const submitBtn = document.getElementById('submitBtn');
    const uploadForm = document.getElementById('uploadForm');

    console.log('📁 Elementos de arquivo:', {
        fileInput: !!fileInput,
        selectedFiles: !!selectedFiles,
        filesList: !!filesList,
        submitBtn: !!submitBtn,
        uploadForm: !!uploadForm
    });

    if (fileInput && selectedFiles && filesList && submitBtn && uploadForm) {
        fileInput.addEventListener('change', function() {
            const files = this.files;
            console.log('📄 Arquivos selecionados:', files);
            
            if (files.length > 0) {
                selectedFiles.classList.remove('hidden');
                filesList.innerHTML = '';
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    console.log('📄 Arquivo:', file.name, 'Tamanho:', file.size, 'Tipo:', file.type);
                    
                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'flex items-center justify-between p-2 bg-neutral-50 rounded';
                    fileDiv.innerHTML = `
                        <span class="text-sm text-neutral-700">${file.name}</span>
                        <span class="text-xs text-neutral-500">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                    `;
                    filesList.appendChild(fileDiv);
                }
                
                submitBtn.disabled = false;
                console.log('✅ Botão de envio habilitado');
            } else {
                selectedFiles.classList.add('hidden');
                submitBtn.disabled = true;
                console.log('❌ Nenhum arquivo selecionado');
            }
        });

        uploadForm.addEventListener('submit', function(e) {
            console.log('🚀 Formulário de arquivos sendo enviado...');
            console.log('📋 Dados do formulário:', new FormData(this));
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Enviando...
            `;
        });

        // Initially disable submit button
        submitBtn.disabled = true;
    }

    // Folder upload handling (sempre existe)
    const folderInput = document.getElementById('folderInput');
    const selectedFolder = document.getElementById('selectedFolder');
    const folderInfo = document.getElementById('folderInfo');
    const submitFolderBtn = document.getElementById('submitFolderBtn');
    const folderUploadForm = document.getElementById('folderUploadForm');

    console.log('📁 Elementos de pasta:', {
        folderInput: !!folderInput,
        selectedFolder: !!selectedFolder,
        folderInfo: !!folderInfo,
        submitFolderBtn: !!submitFolderBtn,
        folderUploadForm: !!folderUploadForm
    });

    if (folderInput && selectedFolder && folderInfo && submitFolderBtn && folderUploadForm) {
        folderInput.addEventListener('change', function() {
            const file = this.files[0];
            console.log('📦 Pasta selecionada:', file);
            
            if (file) {
                console.log('📦 Detalhes da pasta:', {
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    lastModified: file.lastModified
                });
                
                // Validar se é um arquivo ZIP
                if (file.type !== 'application/zip' && file.type !== 'application/x-zip-compressed') {
                    console.log('❌ Tipo de arquivo inválido:', file.type);
                    alert('❌ Por favor, selecione apenas arquivos ZIP (.zip)');
                    this.value = '';
                    selectedFolder.classList.add('hidden');
                    submitFolderBtn.disabled = true;
                    return;
                }
                
                // Validar tamanho (200MB)
                const maxSize = 200 * 1024 * 1024; // 200MB
                if (file.size > maxSize) {
                    console.log('❌ Arquivo muito grande:', file.size, 'bytes');
                    alert('❌ O arquivo é muito grande. Máximo permitido: 200MB');
                    this.value = '';
                    selectedFolder.classList.add('hidden');
                    submitFolderBtn.disabled = true;
                    return;
                }
                
                selectedFolder.classList.remove('hidden');
                folderInfo.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="font-medium">${file.name}</span>
                        <span class="text-neutral-500">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                    </div>
                    <div class="mt-1 text-xs text-green-600">✅ Arquivo ZIP válido</div>
                `;
                submitFolderBtn.disabled = false;
                console.log('✅ Pasta válida selecionada');
            } else {
                selectedFolder.classList.add('hidden');
                submitFolderBtn.disabled = true;
                console.log('❌ Nenhuma pasta selecionada');
            }
        });

        folderUploadForm.addEventListener('submit', function(e) {
            console.log('🚀 Formulário de pasta sendo enviado...');
            console.log('🔍 DEBUG: Formulário encontrado:', this);
            console.log('🔍 DEBUG: Action:', this.action);
            console.log('🔍 DEBUG: Method:', this.method);
            console.log('🔍 DEBUG: Enctype:', this.enctype);
            
            // Verificar se há arquivo selecionado
            const file = folderInput.files[0];
            if (!file) {
                console.log('❌ Nenhum arquivo selecionado para envio');
                e.preventDefault();
                alert('❌ Por favor, selecione um arquivo ZIP primeiro');
                return;
            }
            
            console.log('📋 Dados do formulário de pasta:', {
                fileName: file.name,
                fileSize: file.size,
                fileType: file.type,
                formAction: this.action,
                formMethod: this.method
            });
            
            submitFolderBtn.disabled = true;
            submitFolderBtn.innerHTML = `
                <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Enviando Pasta...
            `;
            
            console.log('✅ Formulário enviado com sucesso');
        });

        // Initially disable submit button
        submitFolderBtn.disabled = true;
        console.log('🔧 Script de upload de pasta configurado');
    } else {
        console.log('❌ Elementos de pasta não encontrados');
    }
});
</script>
@endsection
