@extends('layouts.dashboard')

@section('title', 'Pasta: ' . $folder['name'] . ' - BSDrive')

@section('content')
<div class="min-h-screen bg-neutral-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-neutral-900 flex items-center">
                <svg class="w-8 h-8 mr-3 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M10 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2h-8l-2-2z"/>
                </svg>
                {{ $folder['name'] }}
            </h1>
            <div class="flex gap-2">
                <button onclick="document.getElementById('file-upload').click()" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Enviar Arquivo
                </button>
                <a href="{{ route('folders.create', ['parent_id' => $folder['id']]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Nova Pasta
                </a>
                <button type="button" onclick="document.getElementById('zip-folder-input-gd').click()" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 4H6a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z"></path>
                    </svg>
                    Enviar Pasta ZIP
                </button>
                <a href="{{ route('folders.edit', $folder['id']) }}" 
                   class="inline-flex items-center px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 font-medium rounded-lg border border-neutral-300 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Renomear
                </a>
                @if(isset($folder['parents']) && count($folder['parents']) > 0)
                    <a href="{{ route('folders.show', $folder['parents'][0]) }}" 
                       class="inline-flex items-center px-4 py-2 bg-neutral-600 hover:bg-neutral-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                        </svg>
                        Pasta Pai
                    </a>
                @else
                    <a href="{{ route('folders.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-neutral-600 hover:bg-neutral-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Raiz
                    </a>
                @endif
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('error') }}
                </div>
            </div>
        @endif

        <!-- Subfolders Section -->
        <div class="mb-8">
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h2 class="text-lg font-semibold text-neutral-900 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M10 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2h-8l-2-2z"/>
                        </svg>
                        Subpastas
                        <span class="ml-2 px-2 py-1 bg-neutral-100 text-neutral-700 text-sm rounded-full">{{ count($subfolders) }}</span>
                    </h2>
                </div>
                <div class="p-6">
                    @if(count($subfolders) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            @foreach($subfolders as $subfolder)
                                <div class="bg-neutral-50 rounded-lg border border-neutral-200 p-4 hover:shadow-md transition-shadow duration-200">
                                    <div class="text-center">
                                        <svg class="w-12 h-12 mx-auto text-amber-500 mb-3" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M10 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2h-8l-2-2z"/>
                                        </svg>
                                        <h3 class="font-medium text-neutral-900 mb-3 text-sm">{{ $subfolder['name'] }}</h3>
                                        <div class="space-y-2">
                                            <a href="{{ route('folders.show', $subfolder['id']) }}" 
                                               class="w-full inline-flex items-center justify-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                Abrir
                                            </a>
                                            <a href="{{ route('folders.edit', $subfolder['id']) }}" 
                                               class="w-full inline-flex items-center justify-center px-3 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-sm font-medium rounded-lg border border-neutral-300 transition-colors duration-200">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                Renomear
                                            </a>
                                            <button 
                                                onclick="confirmDelete('{{ $subfolder['id'] }}', '{{ $subfolder['name'] }}')" 
                                                class="w-full inline-flex items-center justify-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                                title="Excluir subpasta"
                                            >
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Excluir
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-neutral-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5L12 5H5a2 2 0 00-2 2z"></path>
                            </svg>
                            <p class="text-neutral-500">Nenhuma subpasta encontrada</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Files Section -->
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
            <div class="px-6 py-4 border-b border-neutral-200">
                <h2 class="text-lg font-semibold text-neutral-900 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Arquivos
                    <span class="ml-2 px-2 py-1 bg-neutral-100 text-neutral-700 text-sm rounded-full">{{ count($files) }}</span>
                </h2>
            </div>
            <div class="p-6">
                @if(count($files) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b border-neutral-200">
                                    <th class="text-left py-3 px-4 font-medium text-neutral-700">Nome</th>
                                    <th class="text-left py-3 px-4 font-medium text-neutral-700">Tipo</th>
                                    <th class="text-left py-3 px-4 font-medium text-neutral-700">Tamanho</th>
                                    <th class="text-left py-3 px-4 font-medium text-neutral-700">Criado em</th>
                                    <th class="text-left py-3 px-4 font-medium text-neutral-700">Modificado em</th>
                                    <th class="text-left py-3 px-4 font-medium text-neutral-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($files as $file)
                                    <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                                        <td class="py-3 px-4">
                                            <div class="flex items-center">
                                                @php 
                                                    $mime = $file['mimeType'] ?? null;
                                                    $isImage = $mime && \Illuminate\Support\Str::startsWith($mime, 'image/');
                                                    $isPdf = $mime === 'application/pdf';
                                                @endphp
                                                @if($isImage)
                                                    <img src="{{ route('files.view-image', $file['id']) }}" alt="thumb" class="w-10 h-10 object-cover rounded mr-2 border border-neutral-200" onerror="this.style.display='none'"/>
                                                @else
                                                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                @endif
                                                {{ $file['name'] }}
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 bg-neutral-100 text-neutral-700 text-xs rounded-full">{{ $file['mimeType'] ?? 'N/A' }}</span>
                                        </td>
                                        <td class="py-3 px-4 text-sm text-neutral-600">
                                            @if(isset($file['size']))
                                                {{ number_format($file['size'] / 1024, 2) }} KB
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-sm text-neutral-600">
                                            @if(isset($file['createdTime']))
                                                {{ \Carbon\Carbon::parse($file['createdTime'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-sm text-neutral-600">
                                            @if(isset($file['modifiedTime']))
                                                {{ \Carbon\Carbon::parse($file['modifiedTime'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-2">
                                                <!-- Botão Visualizar -->
                                                <a href="{{ (isset($file['mimeType']) && (\Illuminate\Support\Str::startsWith($file['mimeType'], 'image/') || $file['mimeType'] === 'application/pdf')) ? route('files.view-image', $file['id']) : route('files.view', $file['id']) }}" 
                                                   class="inline-flex items-center px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm rounded-lg transition-colors duration-200"
                                                   title="Visualizar arquivo">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </a>
                                                
                                                <!-- Botão Download -->
                                                <a href="{{ route('files.download', $file['id']) }}" 
                                                   class="inline-flex items-center px-2 py-1 bg-green-100 hover:bg-green-200 text-green-700 text-sm rounded-lg transition-colors duration-200"
                                                   title="Baixar arquivo">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                </a>
                                                
                                                <!-- Botão Delete -->
                                                <form method="POST" action="{{ route('files.destroy', $file['id']) }}" 
                                                      style="display: inline;"
                                                      onsubmit="return confirm('Tem certeza que deseja excluir este arquivo?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="inline-flex items-center px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-sm rounded-lg transition-colors duration-200"
                                                            title="Excluir arquivo">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                                
                                                <!-- Botão Preview (mantido) -->
                                                <button type="button" class="inline-flex items-center px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm rounded-lg transition-colors duration-200"
                                                        title="Visualizar arquivo">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                                    </svg>
                                                </button>
                                                <button type="button" class="inline-flex items-center px-2 py-1 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-sm rounded-lg transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-neutral-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-neutral-500">Nenhum arquivo encontrado</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Formulário oculto para upload de arquivos -->
<form id="upload-form" style="display: none;" enctype="multipart/form-data">
    @csrf
    <input type="file" id="file-upload" name="files[]" accept="*/*" multiple>
    <input type="hidden" name="parent_id" value="{{ $folder['id'] }}">
</form>

<!-- Formulário oculto para upload de pasta ZIP nesta pasta -->
<form id="zip-upload-form-gd" action="{{ route('files.upload-folder') }}" method="POST" enctype="multipart/form-data" style="display:none;">
    @csrf
    <input type="hidden" name="parent_id" value="{{ $folder['id'] }}">
    <input type="file" id="zip-folder-input-gd" name="folder" accept=".zip" required>
</form>

<!-- Modal de progresso -->
<div id="upload-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Enviando Arquivo</h3>
        <div class="space-y-4">
            <div id="upload-progress" class="space-y-2">
                <!-- Progress items will be added here -->
            </div>
            <button onclick="closeUploadModal()" class="w-full px-4 py-2 bg-neutral-600 hover:bg-neutral-700 text-white rounded-lg transition-colors duration-200">
                Fechar
            </button>
        </div>
    </div>
</div>

<script>
document.getElementById('file-upload').addEventListener('change', function(e) {
    const files = e.target.files;
    if (files.length > 0) {
        uploadFiles(files);
    }
});

function uploadFiles(files) {
    document.getElementById('upload-modal').classList.remove('hidden');
    document.getElementById('upload-modal').classList.add('flex');
    
    const progressContainer = document.getElementById('upload-progress');
    progressContainer.innerHTML = '';
    
    Array.from(files).forEach((file, index) => {
        uploadFile(file, index, progressContainer);
    });
}

function uploadFile(file, index, container) {
    const progressItem = document.createElement('div');
    progressItem.className = 'space-y-2';
    progressItem.innerHTML = `
        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-neutral-700 truncate">${file.name}</span>
            <span id="status-${index}" class="text-sm text-neutral-500">Preparando...</span>
        </div>
        <div class="w-full bg-neutral-200 rounded-full h-2">
            <div id="progress-${index}" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
        </div>
    `;
    container.appendChild(progressItem);
    
    const formData = new FormData();
    formData.append('files[]', file); // Mudança aqui: files[] em vez de file
    formData.append('parent_id', '{{ $folder["id"] }}'); // Mudança: parent_id em vez de folder_id

    // Obter CSRF token com fallback robusto
    let csrfToken = null;
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken && metaToken.content) {
        csrfToken = metaToken.content;
    } else {
        const inputToken = document.querySelector('input[name="_token"]');
        if (inputToken && inputToken.value) {
            csrfToken = inputToken.value;
        }
    }
    if (csrfToken) {
        formData.append('_token', csrfToken);
    } else {
        console.warn('CSRF token não encontrado na página. O upload pode falhar com 419.');
    }
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            document.getElementById(`progress-${index}`).style.width = percentComplete + '%';
            document.getElementById(`status-${index}`).textContent = Math.round(percentComplete) + '%';
        }
    });
    
    xhr.addEventListener('load', function() {
        // Tentar detectar redirecionamento para login (quando resposta é HTML de login)
        const isHtml = (xhr.getResponseHeader('content-type') || '').includes('text/html');
        if (xhr.status === 200 && !isHtml) {
            document.getElementById(`status-${index}`).textContent = 'Concluído';
            document.getElementById(`status-${index}`).className = 'text-sm text-green-600';
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            let statusMsg = `Erro (status ${xhr.status})`;
            if (xhr.status === 419) statusMsg = 'Erro CSRF (419)';
            if (xhr.status === 302 || (xhr.status === 200 && isHtml)) statusMsg = 'Sessão expirada/Login requerido';
            document.getElementById(`status-${index}`).textContent = statusMsg;
            document.getElementById(`status-${index}`).className = 'text-sm text-red-600';
            console.error('Falha no upload:', { status: xhr.status, response: xhr.responseText });
        }
    });
    
    xhr.addEventListener('error', function() {
        document.getElementById(`status-${index}`).textContent = 'Erro';
        document.getElementById(`status-${index}`).className = 'text-sm text-red-600';
    });
    
    xhr.open('POST', '/files', true);
    // Bonus: indicar requisição AJAX e enviar CSRF também por header
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    if (csrfToken) {
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    }
    console.log('Enviando arquivo...', { name: file.name, size: file.size, type: file.type });
    xhr.send(formData);
}

function closeUploadModal() {
    document.getElementById('upload-modal').classList.add('hidden');
    document.getElementById('upload-modal').classList.remove('flex');
    document.getElementById('file-upload').value = '';
}

// Delete confirmation modal
function confirmDelete(folderId, folderName) {
    document.getElementById('folderName').textContent = folderName;
    document.getElementById('deleteModal').classList.remove('hidden');

    document.getElementById('confirmDelete').onclick = function() {
        // Criar um formulário temporário para enviar a requisição DELETE
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/folders/${folderId}`;
        
        // Adicionar token CSRF
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfToken);
        
        // Adicionar método DELETE
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        form.appendChild(methodField);
        
        // Adicionar formulário ao DOM e enviar
        document.body.appendChild(form);
        form.submit();
    };

    document.getElementById('cancelDelete').onclick = function() {
        document.getElementById('deleteModal').classList.add('hidden');
    };

    // Close modal when clicking outside
    document.getElementById('deleteModal').onclick = function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    };
}
</script>

<script>
// Enviar Pasta ZIP (submit automático ao escolher arquivo) para visão Google Drive
(function(){
    const zipInput = document.getElementById('zip-folder-input-gd');
    if (!zipInput) return;
    zipInput.addEventListener('change', function() {
        if (!zipInput.files || zipInput.files.length === 0) return;
        const file = zipInput.files[0];
        if (file && file.name && !file.name.toLowerCase().endsWith('.zip')) {
            alert('❌ Por favor, selecione um arquivo ZIP (.zip)');
            zipInput.value = '';
            return;
        }
        document.getElementById('zip-upload-form-gd').submit();
    });
})();
</script>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Confirmar Exclusão</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Tem certeza que deseja excluir a pasta <strong id="folderName"></strong>?
                </p>
                <p class="text-xs text-gray-400 mt-2">
                    Esta ação não pode ser desfeita. Todos os arquivos e subpastas serão removidos.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="cancelDelete" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancelar
                </button>
                <button id="confirmDelete" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Excluir
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
