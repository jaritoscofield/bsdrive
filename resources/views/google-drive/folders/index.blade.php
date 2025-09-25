@extends('layouts.dashboard')

@section('title', 'Minha Pasta - BSDrive')

@section('content')
<div class="min-h-screen bg-neutral-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-neutral-900 flex items-center">
                <svg class="w-8 h-8 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                </svg>
                @if($parentId && $parentId === auth()->user()->getPersonalFolderId())
                    Minha Pasta Pessoal
                @elseif($parentId)
                    Subpasta
                @else
                    Pastas do BSDrive
                @endif
            </h1>
            <div class="flex space-x-3">
                <button onclick="document.getElementById('file-upload').click()" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Enviar Arquivo
                </button>
                <button onclick="document.getElementById('folder-upload').click()" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 4H6a2 2 0 00-2 2v16a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z"></path>
                    </svg>
                    Enviar Pasta ZIP
                </button>
                <a href="{{ route('folders.create', ['parent_id' => $parentId]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Nova Pasta
                </a>
                @if($parentId && $parentId !== auth()->user()->getPersonalFolderId())
                    <a href="{{ route('folders.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-neutral-600 hover:bg-neutral-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        </svg>
                        Minha Pasta
                    </a>
                @endif
            </div>
        </div>

        @if($parentId && $parentId === auth()->user()->getPersonalFolderId())
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-blue-900">Esta é sua pasta pessoal no BSDrive.</p>
                        <p class="text-blue-700 text-sm">Apenas você pode ver e modificar o conteúdo desta pasta.</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-green-800 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-red-800 font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <!-- Main Content Card -->
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
            <div class="px-6 py-4 border-b border-neutral-200">
                <h2 class="text-lg font-semibold text-neutral-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-neutral-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                    </svg>
                    @if($parentId && $parentId === auth()->user()->getPersonalFolderId())
                        Pastas na sua área pessoal
                    @elseif($parentId)
                        Subpastas
                    @else
                        Suas pastas
                    @endif
                    <span class="ml-2 px-2 py-1 text-xs font-medium bg-neutral-100 text-neutral-600 rounded-full">{{ count($folders) }}</span>
                </h2>
            </div>
            <div class="p-6">
                @if(count($folders) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($folders as $folder)
                            <div class="bg-neutral-50 rounded-lg border border-neutral-200 hover:border-blue-300 hover:shadow-md transition-all duration-200 overflow-hidden">
                                <div class="p-6 text-center">
                                    <svg class="w-12 h-12 mx-auto text-blue-500 mb-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                                    </svg>
                                    <h3 class="font-medium text-neutral-900 mb-4 text-sm break-words">{{ $folder['name'] }}</h3>
                                    <div class="space-y-2">
                                        <a href="{{ route('folders.show', $folder['id']) }}" 
                                           class="w-full inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors duration-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Abrir
                                        </a>
                                        <a href="{{ route('folders.edit', $folder['id']) }}" 
                                           class="w-full inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-neutral-600 bg-neutral-100 hover:bg-neutral-200 rounded-md transition-colors duration-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Renomear
                                        </a>
                                        <form action="{{ route('folders.destroy', $folder['id']) }}" 
                                              method="POST" 
                                              class="w-full"
                                              onsubmit="return confirm('Tem certeza que deseja excluir esta pasta?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-md transition-colors duration-200">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-neutral-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-neutral-500 mt-4 mb-2">Nenhuma pasta encontrada</h3>
                        <p class="text-neutral-400 mb-6">
                            @if($parentId && $parentId === auth()->user()->getPersonalFolderId())
                                Sua pasta pessoal não contém subpastas ainda.
                            @elseif($parentId)
                                Esta pasta não contém subpastas.
                            @else
                                Você não tem acesso a nenhuma pasta compartilhada.
                            @endif
                        </p>
                        <a href="{{ route('folders.create', ['parent_id' => $parentId]) }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Criar Primeira Pasta
                        </a>
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
    <input type="hidden" name="parent_id" value="{{ $parentId }}">
</form>

<!-- Formulário oculto para upload de pasta ZIP -->
<form id="folder-upload-form" style="display: none;" enctype="multipart/form-data">
    @csrf
    <input type="file" id="folder-upload" name="folder" accept=".zip" required>
</form>

<!-- Modal de progresso -->
<div id="upload-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Enviando Arquivo/Pasta</h3>
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

document.getElementById('folder-upload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        uploadFolder(file);
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
    formData.append('parent_id', '{{ $parentId }}'); // Mudança: parent_id em vez de folder_id
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            document.getElementById(`progress-${index}`).style.width = percentComplete + '%';
            document.getElementById(`status-${index}`).textContent = Math.round(percentComplete) + '%';
        }
    });
    
    xhr.addEventListener('load', function() {
        if (xhr.status === 200) {
            document.getElementById(`status-${index}`).textContent = 'Concluído';
            document.getElementById(`status-${index}`).className = 'text-sm text-green-600';
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            document.getElementById(`status-${index}`).textContent = 'Erro';
            document.getElementById(`status-${index}`).className = 'text-sm text-red-600';
        }
    });
    
    xhr.addEventListener('error', function() {
        document.getElementById(`status-${index}`).textContent = 'Erro';
        document.getElementById(`status-${index}`).className = 'text-sm text-red-600';
    });
    
    xhr.open('POST', '/files', true);
    xhr.send(formData);
}

function uploadFolder(file) {
    // Validar se é um arquivo ZIP
    if (file.type !== 'application/zip' && file.type !== 'application/x-zip-compressed') {
        alert('❌ Por favor, selecione apenas arquivos ZIP (.zip)');
        document.getElementById('folder-upload').value = '';
        return;
    }
    
    // Validar tamanho (200MB)
    const maxSize = 200 * 1024 * 1024; // 200MB
    if (file.size > maxSize) {
        alert('❌ O arquivo é muito grande. Máximo permitido: 200MB');
        document.getElementById('folder-upload').value = '';
        return;
    }
    
    // Mostrar modal de progresso
    document.getElementById('upload-modal').classList.remove('hidden');
    document.getElementById('upload-modal').classList.add('flex');
    
    const progressContainer = document.getElementById('upload-progress');
    progressContainer.innerHTML = `
        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-neutral-700 truncate">${file.name}</span>
                <span id="folder-status" class="text-sm text-neutral-500">Preparando...</span>
            </div>
            <div class="w-full bg-neutral-200 rounded-full h-2">
                <div id="folder-progress" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>
    `;
    
    const formData = new FormData();
    formData.append('folder', file);
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            document.getElementById('folder-progress').style.width = percentComplete + '%';
            document.getElementById('folder-status').textContent = Math.round(percentComplete) + '%';
        }
    });
    
    xhr.addEventListener('load', function() {
        if (xhr.status === 200) {
            document.getElementById('folder-status').textContent = 'Concluído';
            document.getElementById('folder-status').className = 'text-sm text-green-600';
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            document.getElementById('folder-status').textContent = 'Erro';
            document.getElementById('folder-status').className = 'text-sm text-red-600';
        }
    });
    
    xhr.addEventListener('error', function() {
        document.getElementById('folder-status').textContent = 'Erro';
        document.getElementById('folder-status').className = 'text-sm text-red-600';
    });
    
    xhr.open('POST', '/files/upload-folder', true);
    xhr.send(formData);
}

function closeUploadModal() {
    document.getElementById('upload-modal').classList.add('hidden');
    document.getElementById('upload-modal').classList.remove('flex');
    document.getElementById('file-upload').value = '';
    document.getElementById('folder-upload').value = '';
}
</script>
@endsection
